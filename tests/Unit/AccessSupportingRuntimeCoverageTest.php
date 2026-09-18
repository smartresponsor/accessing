<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Authenticator\AccessAuthenticationEntryPoint;
use App\Accessing\Contract\AccessIntegrationContract;
use App\Accessing\DTO\AccessExternalIdentityProfileDTO;
use App\Accessing\DTO\AccessPasskeyAssertionResultDTO;
use App\Accessing\DTO\AccessPasskeyAttestationResultDTO;
use App\Accessing\DTO\AccessPasskeyAuthenticationOptionsDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\DTO\Config\AccessEnvironmentConfigDTO;
use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessExternalIdentityEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\Entity\AccessMobileSessionEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessSessionEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\Factory\Surface\AccessHomeSurfaceContractFactory;
use App\Accessing\Policy\Lifecycle\AccessLifecyclePolicy;
use App\Accessing\Provider\Configuration\AccessConfigurationToolProvider;
use App\Accessing\RepositoryInterface\AccessExternalIdentityRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\Service\Config\AccessEnvironmentConfigService;
use App\Accessing\Service\OAuth\AccessExternalAuthenticationService;
use App\Accessing\Service\OAuth\AccessGoogleOAuthService;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessEmailAddress;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use App\Accessing\ValueObject\AccessPhoneNumber;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\ValueObject\AccessVerificationChallengeType;
use App\Accessing\Verifier\Passkey\AccessFailClosedPasskeyAssertionVerifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AccessSupportingRuntimeCoverageTest extends TestCase
{
    public function testConfigurationProviderReadsCanonicalComponentContract(): void
    {
        $provider = new AccessConfigurationToolProvider();

        self::assertSame('Accessing', $provider->componentKey());
        self::assertSame('accessing', $provider->componentToken());

        $tools = iterator_to_array($provider->tools());
        self::assertCount(1, $tools);
        self::assertSame('Environment', $tools[0]->toolSlug);
        self::assertSame(AccessEnvironmentConfigService::class, $tools[0]->serviceClass);
        self::assertTrue($tools[0]->executable);

        $contract = $provider->integrationContract();
        self::assertSame('authentication_session_identity', $contract->owns);
        self::assertSame('accessing:user:', $contract->subjectPrefix);
    }

    public function testEnvironmentConfigServiceReadsCanonicalRuntimeManifestAndBuildsSafeChanges(): void
    {
        $service = new AccessEnvironmentConfigService();
        $descriptor = $service->descriptor();

        self::assertSame('Accessing', $descriptor->applicationCode);
        self::assertSame('accessing.environment', $descriptor->toolCode);
        self::assertSame(['config/component/access_runtime.yaml'], $descriptor->readableFiles);
        self::assertSame(['config/component/access_runtime.yaml'], $descriptor->writableFiles);

        $variables = iterator_to_array($service->managedVariables());
        self::assertCount(7, $variables);
        self::assertSame('config/component/access_runtime.yaml', $variables[0]->targetFile);
        self::assertSame('accessing_mailer_sender', $variables[0]->key);
        self::assertTrue($variables[0]->required);

        $data = $service->loadData();
        self::assertInstanceOf(AccessEnvironmentConfigDTO::class, $data);
        self::assertSame('no-reply@example.test', $data->mailerSender);
        self::assertSame('fake', $data->phoneVerificationProvider);
        self::assertSame('30', $data->sessionMaxIdleDays);

        $loadedVariables = $service->loadVariableData();
        self::assertSame(30, $loadedVariables['accessing_session_max_idle_days']);
        self::assertSame(5, $loadedVariables['accessing_user_lock_threshold']);

        $staged = $service->save($data);
        self::assertSame('pending', $staged['status']);
        self::assertSame('30', $staged['masked_changes']['accessing_session_max_idle_days']);

        $variableStage = $service->saveVariables([
            'accessing_mailer_sender' => 'ops@example.test',
            'accessing_phone_verification_provider' => 'null',
            'accessing_session_max_idle_days' => '45',
            'accessing_recovery_code_ttl_minutes' => 40,
            'accessing_verification_code_ttl_minutes' => '12',
            'accessing_user_lock_threshold' => '7',
            'accessing_user_lock_minutes' => 20,
        ]);
        self::assertSame('pending', $variableStage['status']);
        self::assertSame('45', $variableStage['masked_changes']['accessing_session_max_idle_days']);
        self::assertSame('ops@example.test', $variableStage['masked_changes']['accessing_mailer_sender']);

        $this->expectException(\InvalidArgumentException::class);
        $service->save(new \stdClass());
    }

    public function testEnvironmentConfigNormalizationHelpersCoverAllValueKinds(): void
    {
        $service = new AccessEnvironmentConfigService();
        $reflection = new \ReflectionClass($service);

        $stringValue = $reflection->getMethod('stringValue');
        self::assertSame('text', $stringValue->invoke(null, 'text', 'fallback'));
        self::assertSame('7', $stringValue->invoke(null, 7, 'fallback'));
        self::assertSame('fallback', $stringValue->invoke(null, null, 'fallback'));
        self::assertSame('fallback', $stringValue->invoke(null, ['invalid'], 'fallback'));

        $intValue = $reflection->getMethod('intValue');
        self::assertSame(7, $intValue->invoke(null, 7, 30));
        self::assertSame(45, $intValue->invoke(null, '45', 30));
        self::assertSame(30, $intValue->invoke(null, 'invalid', 30));

        $stringMap = $reflection->getMethod('stringMap');
        self::assertSame([], $stringMap->invoke(null, []));
        self::assertSame(
            ['string' => 'value', 'int' => '2', 'null' => '', 'array' => ''],
            $stringMap->invoke(null, ['string' => 'value', 'int' => 2, 'null' => null, 'array' => []]),
        );

        $stringKeyMap = $reflection->getMethod('stringKeyMap');
        self::assertSame([], $stringKeyMap->invoke(null, []));
        self::assertSame(['named' => 'value'], $stringKeyMap->invoke(null, [0 => 'ignored', 'named' => 'value']));

        $defaults = $service->saveVariables([]);
        self::assertSame('', $defaults['masked_changes']['accessing_mailer_sender']);
        self::assertSame('fake', $defaults['masked_changes']['accessing_phone_verification_provider']);
        self::assertSame('30', $defaults['masked_changes']['accessing_session_max_idle_days']);

        $invalidNumbers = $service->saveVariables([
            'accessing_mailer_sender' => 123,
            'accessing_phone_verification_provider' => ['invalid'],
            'accessing_session_max_idle_days' => 'invalid',
            'accessing_recovery_code_ttl_minutes' => null,
            'accessing_verification_code_ttl_minutes' => [],
            'accessing_user_lock_threshold' => 6.8,
            'accessing_user_lock_minutes' => '20',
        ]);
        self::assertSame('123', $invalidNumbers['masked_changes']['accessing_mailer_sender']);
        self::assertSame('fake', $invalidNumbers['masked_changes']['accessing_phone_verification_provider']);
        self::assertSame('30', $invalidNumbers['masked_changes']['accessing_session_max_idle_days']);
        self::assertSame('6', $invalidNumbers['masked_changes']['accessing_user_lock_threshold']);
        self::assertSame('20', $invalidNumbers['masked_changes']['accessing_user_lock_minutes']);
    }

    public function testIntegrationAndHomeSurfaceContractsExposeStableFallbacks(): void
    {
        $default = AccessIntegrationContract::fromYaml([]);
        self::assertSame('authentication_session_identity', $default->owns);
        self::assertSame('accessing:user:', $default->subjectPrefix);

        $custom = AccessIntegrationContract::fromYaml(['owns' => 123, 'subject_prefix' => 'custom:']);
        self::assertSame('123', $custom->owns);
        self::assertSame('custom:', $custom->subjectPrefix);

        $user = new AccessEntity('surface@example.test', 'Surface');
        $surface = (new AccessHomeSurfaceContractFactory('Accessing Product'))->create($user, ['event']);
        self::assertSame('access', $surface->word);
        self::assertSame('access.overview', $surface->view);
        self::assertSame('access/index.html.twig', $surface->templateName());
        self::assertSame('Accessing Product', $surface->toTemplateContext()['accessingProductName']);
        self::assertSame($user, $surface->toTemplateContext()['user']);
        self::assertSame(['event'], $surface->toTemplateContext()['events']);
        self::assertSame('access.overview', $surface->toFallbackData()['view']);
    }

    public function testAuthenticationEntryPointStoresSafeTargetPathAndRedirects(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $request = Request::create('https://example.test/access/security', 'GET');
        $request->setSession($session);

        $response = (new AccessAuthenticationEntryPoint())->start($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/access/signin', $response->getTargetUrl());
        self::assertSame('https://example.test/access/security', $session->get('_security.main.target_path'));
    }

    public function testGoogleOauthFailsClosedWhenDisabledOrStateInvalid(): void
    {
        $urls = $this->createMock(UrlGeneratorInterface::class);
        $urls->method('generate')->willReturn('/access/google/callback');
        $disabled = new AccessGoogleOAuthService($urls, false, '', '', '');

        self::assertFalse($disabled->isEnabled());

        $request = Request::create('/callback?code=x&state=y');
        $request->setSession(new Session(new MockArraySessionStorage()));

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Google sign-in state validation failed.');
        $disabled->complete($request);
    }

    public function testExternalAuthenticationResolvesExistingIdentityAndCreatesNewIdentity(): void
    {
        $profile = new AccessExternalIdentityProfileDTO(
            'google',
            'subject-1',
            'oauth@example.test',
            true,
            'OAuth User',
            'https://example.test/avatar.png',
        );
        $request = Request::create('/oauth/callback');
        $existingUser = new AccessEntity('oauth@example.test', 'Existing');
        $existingIdentity = new AccessExternalIdentityEntity(
            $existingUser,
            'google',
            'subject-1',
            'old@example.test',
            false,
        );

        $external = $this->createMock(AccessExternalIdentityRepositoryInterface::class);
        $external->expects(self::once())
            ->method('findOneByProviderAndSubject')
            ->with('google', 'subject-1')
            ->willReturn($existingIdentity);
        $external->expects(self::once())->method('save')->with($existingIdentity, true);
        $users = $this->createMock(AccessRepositoryInterface::class);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);

        $service = new AccessExternalAuthenticationService($external, $users, $events);
        self::assertSame($existingUser, $service->resolve($profile, $request));
        self::assertSame('oauth@example.test', $existingIdentity->getEmail());
        self::assertTrue($existingIdentity->isEmailVerified());

        $newExternal = $this->createMock(AccessExternalIdentityRepositoryInterface::class);
        $newExternal->method('findOneByProviderAndSubject')->willReturn(null);
        $newExternal->expects(self::once())->method('save');
        $newUsers = $this->createMock(AccessRepositoryInterface::class);
        $newUsers->method('findOneByEmailAddress')->willReturn(null);
        $newUsers->expects(self::once())->method('save');
        $newEvents = $this->createMock(AccessSecurityEventServiceInterface::class);
        $newEvents->expects(self::once())->method('record');

        $created = (new AccessExternalAuthenticationService($newExternal, $newUsers, $newEvents))->resolve(
            new AccessExternalIdentityProfileDTO('google', 'subject-2', 'new@example.test', true, 'New User'),
            $request,
        );
        self::assertSame('new@example.test', $created->getEmail());
        self::assertTrue($created->isEmailVerified());
    }

    public function testExternalAuthenticationRejectsEmailConflict(): void
    {
        $external = $this->createMock(AccessExternalIdentityRepositoryInterface::class);
        $external->method('findOneByProviderAndSubject')->willReturn(null);
        $user = new AccessEntity('conflict@example.test');
        $users = $this->createMock(AccessRepositoryInterface::class);
        $users->method('findOneByEmailAddress')->willReturn($user);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record');

        $service = new AccessExternalAuthenticationService($external, $users, $events);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('An account with this email already exists.');
        $service->resolve(
            new AccessExternalIdentityProfileDTO('google', 'subject-conflict', 'conflict@example.test', true),
            Request::create('/oauth/callback'),
        );
    }

    public function testFailClosedPasskeyVerifierRejectsUnavailableVerification(): void
    {
        $relyingParty = new AccessPasskeyRelyingPartyConfigDTO(
            'example.test',
            'Example',
            'https://example.test',
        );

        $this->expectException(AccessPasskeyVerificationUnavailableException::class);
        (new AccessFailClosedPasskeyAssertionVerifier())->verify([], 'challenge', $relyingParty, 'public-key', 'user-handle');
    }

    public function testExternalIdentityProfileRejectsMissingRequiredIdentity(): void
    {
        foreach ([
            ['', 'subject', 'mail@example.test'],
            ['google', '', 'mail@example.test'],
            ['google', 'subject', ''],
        ] as [$provider, $subject, $email]) {
            self::assertInvalid(static fn () => new AccessExternalIdentityProfileDTO($provider, $subject, $email, false));
        }
    }

    public function testCoverageClosureForDtoAndValueObjectContracts(): void
    {
        $assertion = new AccessPasskeyAssertionResultDTO('credential', 'handle', 1, 'record');
        self::assertSame(1, $assertion->signCount);
        self::assertInvalid(static fn () => new AccessPasskeyAssertionResultDTO('', 'handle', 0));
        self::assertInvalid(static fn () => new AccessPasskeyAssertionResultDTO('credential', '', 0));
        self::assertInvalid(static fn () => new AccessPasskeyAssertionResultDTO('credential', 'handle', -1));

        $attestation = new AccessPasskeyAttestationResultDTO('credential', 'handle', 'public-key', ['internal'], 1, 'record');
        self::assertSame(['internal'], $attestation->transports);
        self::assertInvalid(static fn () => new AccessPasskeyAttestationResultDTO('', 'handle', 'public-key', []));
        self::assertInvalid(static fn () => new AccessPasskeyAttestationResultDTO('credential', '', 'public-key', []));
        self::assertInvalid(static fn () => new AccessPasskeyAttestationResultDTO('credential', 'handle', '', []));
        self::assertInvalid(static fn () => new AccessPasskeyAttestationResultDTO('credential', 'handle', 'public-key', [], -1));

        $options = new AccessPasskeyAuthenticationOptionsDTO('challenge', 'example.test', []);
        self::assertSame('challenge', $options->toArray()['publicKey']['challenge']);
        self::assertInvalid(static fn () => new AccessPasskeyAuthenticationOptionsDTO('', 'example.test', []));
        self::assertInvalid(static fn () => new AccessPasskeyAuthenticationOptionsDTO('challenge', '', []));

        self::assertSame('http://localhost', (new AccessPasskeyRelyingPartyConfigDTO('localhost', 'Local', 'http://localhost'))->origin);
        self::assertInvalid(static fn () => new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'example.test'));
        self::assertInvalid(static fn () => new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'ftp://example.test'));
        self::assertInvalid(static fn () => new AccessPasskeyRelyingPartyConfigDTO('example.test', 'Example', 'http://example.test'));

        $email = new AccessEmailAddress(' USER@Example.Test ');
        self::assertSame('user@example.test', $email->toString());
        self::assertSame('user@example.test', (string) $email);
        self::assertInvalid(static fn () => new AccessEmailAddress('invalid'));

        $phone = new AccessPhoneNumber('(713) 555-0101');
        self::assertSame('+17135550101', $phone->toString());
        self::assertSame('+17135550101', (string) $phone);
        self::assertSame('+447700900123', (new AccessPhoneNumber('+44 7700 900123'))->toString());
        self::assertInvalid(static fn () => new AccessPhoneNumber(''));
        self::assertInvalid(static fn () => new AccessPhoneNumber('123'));
    }

    public function testCoverageClosureForLifecycleAndIdentityContracts(): void
    {
        self::assertSame('authentication_session_identity', AccessIntegrationContract::fromYaml(['owns' => []])->owns);
        self::assertTrue(AccessLifecyclePolicy::canTransition('active', 'active'));
        self::assertTrue(AccessLifecyclePolicy::canTransition('active', 'locked'));
        self::assertFalse(AccessLifecyclePolicy::canTransition('deleted', 'active'));
        self::assertFalse(AccessLifecyclePolicy::canTransition('unknown', 'active'));
        self::assertFalse(AccessLifecyclePolicy::canTransition('active', 'unknown'));
        self::assertSame(['active', 'disabled', 'deleted'], AccessLifecyclePolicy::allowedTargets('locked'));
        self::assertSame([], AccessLifecyclePolicy::allowedTargets('unknown'));
        AccessLifecyclePolicy::assertCanTransition('registered', 'verified');
        try {
            AccessLifecyclePolicy::assertCanTransition('deleted', 'active');
            self::fail('Invalid transition must throw.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('Invalid Accessing lifecycle transition', $exception->getMessage());
        }

        $user = new AccessEntity('identity-contract@example.test');
        $identity = new AccessExternalIdentityEntity($user, 'Google', 'subject', 'USER@EXAMPLE.TEST', true, ' User ', ' https://example.test/a ');
        self::assertSame($user, $identity->getUser());
        self::assertSame('user@example.test', $identity->getEmail());
        self::assertTrue($identity->isEmailVerified());
        self::assertSame('User', $identity->getDisplayName());
        self::assertSame('https://example.test/a', $identity->getAvatarUrl());
        $at = new \DateTimeImmutable('+1 second');
        $identity->recordAuthentication('NEXT@EXAMPLE.TEST', false, ' ', null, $at);
        self::assertSame('next@example.test', $identity->getEmail());
        self::assertFalse($identity->isEmailVerified());
        self::assertNull($identity->getDisplayName());
        self::assertNull($identity->getAvatarUrl());
        self::assertSame($at, $identity->getLastAuthenticatedAt());

        $nullableIdentity = new AccessExternalIdentityEntity($user, 'google', 'subject-nullable', 'nullable@example.test', false);
        self::assertNull($nullableIdentity->getDisplayName());
        self::assertNull($nullableIdentity->getAvatarUrl());
        $nullableIdentity->recordAuthentication('nullable@example.test', true, null, ' https://example.test/avatar-2 ', null);
        self::assertNull($nullableIdentity->getDisplayName());
        self::assertSame('https://example.test/avatar-2', $nullableIdentity->getAvatarUrl());
    }

    public function testAccessEntityOptionalRelationshipCollectionAndLockBranches(): void
    {
        $empty = new AccessEntity();
        self::assertSame('user', $empty->getUserIdentifier());
        self::assertSame('', $empty->getPassword());
        self::assertNull($empty->getTotpSecret());
        self::assertFalse($empty->isSecondFactorEnabled());

        $displayOnly = new AccessEntity(null, ' Display Only ');
        self::assertSame('Display Only', $displayOnly->getDisplayName());
        $emailOnly = new AccessEntity(' Mixed@Example.Test ');
        self::assertSame('mixed@example.test', $emailOnly->getUserIdentifier());
        $empty->setDisplayName(null)->setPhoneNumber(null);

        $emailVerifiedAt = new \DateTimeImmutable('2026-09-17T12:00:00+00:00');
        $phoneVerifiedAt = new \DateTimeImmutable('2026-09-17T12:01:00+00:00');
        $empty->markEmailVerified($emailVerifiedAt)->markPhoneVerified($phoneVerifiedAt);
        self::assertSame($emailVerifiedAt, $empty->getEmailVerifiedAt());
        self::assertSame($phoneVerifiedAt, $empty->getPhoneVerifiedAt());

        $otherUser = new AccessEntity('other-relations@example.test');
        $credential = new AccessCredentialEntity($otherUser, 'hash');
        $empty->setCredential($credential)->setCredential($credential);
        self::assertSame($empty, $credential->getUser());
        self::assertSame('hash', $empty->getPassword());
        $empty->setCredential(null);
        self::assertNull($empty->getCredential());

        $factor = new AccessSecondFactorEntity($otherUser, 'secret', 'label');
        $empty->setSecondFactor($factor)->setSecondFactor($factor);
        self::assertSame($empty, $factor->getUser());
        self::assertSame('secret', $empty->getTotpSecret());
        self::assertFalse($empty->isSecondFactorEnabled());
        $factor->confirm();
        self::assertTrue($empty->isSecondFactorEnabled());
        $empty->setSecondFactor(null);
        self::assertNull($empty->getSecondFactor());

        $recovery = new AccessRecoveryCodeEntity();
        $empty->addRecoveryCode($recovery)->addRecoveryCode($recovery);
        self::assertCount(1, $empty->getRecoveryCodes());
        $verification = new AccessVerificationChallengeEntity();
        $empty->addVerificationChallenge($verification)->addVerificationChallenge($verification);
        self::assertCount(1, $empty->getVerificationChallenges());
        $session = new AccessSessionEntity();
        $empty->addUserSession($session)->addUserSession($session);
        self::assertCount(1, $empty->getUserSessions());

        self::assertFalse($empty->isLocked());
        $empty->lockUntil(new \DateTimeImmutable('+1 hour'));
        self::assertTrue($empty->isLocked());
        $empty->lockUntil(new \DateTimeImmutable('-1 minute'));
        self::assertFalse($empty->isLocked());
        self::assertNull($empty->getLockedUntil());
        $empty->lock();
        self::assertTrue($empty->isLocked());
        $empty->markSuccessfulSignIn();
        self::assertFalse($empty->isLocked());
        self::assertNotNull($empty->getLastSignInAt());
    }

    public function testCoverageClosureForMobileAndPasskeyEntities(): void
    {
        $user = new AccessEntity('mobile-passkey@example.test');
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $pending = new AccessMobilePendingAuthEntity($user, 'pending-token', AccessMobilePendingPurpose::EmailVerification, 'iPhone', $now, $now->modify('+10 minutes'));
        self::assertTrue($pending->hasToken('pending-token'));
        self::assertFalse($pending->hasToken('wrong-token'));
        self::assertTrue($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now));
        $pending->consume($now->modify('+1 minute'));
        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now->modify('+2 minutes')));
        self::assertInvalid(static fn () => new AccessMobilePendingAuthEntity($user, 'token', AccessMobilePendingPurpose::EmailVerification, 'device', $now, $now));
        self::assertInvalid(static fn () => new AccessMobilePendingAuthEntity($user, '   ', AccessMobilePendingPurpose::EmailVerification, 'device', $now, $now->modify('+1 minute')));
        self::assertInvalid(static fn () => new AccessMobilePendingAuthEntity($user, 'token', AccessMobilePendingPurpose::EmailVerification, '   ', $now, $now->modify('+1 minute')));
        $expiredPending = new AccessMobilePendingAuthEntity($user, 'expired-pending', AccessMobilePendingPurpose::EmailVerification, 'iPhone', $now, $now->modify('+1 minute'));
        self::assertFalse($expiredPending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now->modify('+1 minute')));
        self::assertFalse($expiredPending->isUsable(AccessMobilePendingPurpose::SecondFactor, $now));
        try {
            $expiredPending->consume($now->modify('+2 minutes'));
            self::fail('Expired pending authentication must not be consumed.');
        } catch (\DomainException) {
        }

        $mobileSession = new AccessMobileSessionEntity($user, 'session', 'access', 'refresh', 'iPhone', $now, $now->modify('+15 minutes'), $now->modify('+1 hour'));
        self::assertTrue($mobileSession->hasAccessToken('access'));
        self::assertTrue($mobileSession->hasRefreshToken('refresh'));
        self::assertFalse($mobileSession->hasPreviousRefreshToken('refresh'));
        self::assertTrue($mobileSession->isAccessActive($now));
        self::assertTrue($mobileSession->isRefreshActive($now));
        $mobileSession->rotate('access-2', 'refresh-2', $now->modify('+1 minute'), $now->modify('+16 minutes'), $now->modify('+2 hours'));
        self::assertTrue($mobileSession->hasPreviousRefreshToken('refresh'));
        $mobileSession->markRefreshReuseDetected($now->modify('+2 minutes'));
        self::assertFalse($mobileSession->isRefreshActive($now->modify('+3 minutes')));
        self::assertFalse($mobileSession->isAccessActive($now->modify('+3 minutes')));

        $expirySession = new AccessMobileSessionEntity($user, 'expiry-session', 'expiry-access', 'expiry-refresh', 'iPhone', $now, $now->modify('+5 minutes'), $now->modify('+10 minutes'));
        self::assertFalse($expirySession->isAccessActive($now->modify('+6 minutes')));
        self::assertFalse($expirySession->isRefreshActive($now->modify('+11 minutes')));
        $firstRevoke = $now->modify('+1 minute');
        $expirySession->revoke($firstRevoke);
        $expirySession->revoke($now->modify('+2 minutes'));
        self::assertFalse($expirySession->isAccessActive($now));
        self::assertFalse($expirySession->isRefreshActive($now));

        $challenge = new AccessPasskeyChallengeEntity('challenge', AccessPasskeyCeremonyPurpose::Registration, 'example.test', 'https://example.test/', $now, $now->modify('+5 minutes'), $user);
        self::assertTrue($challenge->isUsable('challenge', AccessPasskeyCeremonyPurpose::Registration, 'example.test', 'https://example.test/', $now));
        $challenge->consume($now->modify('+1 minute'));
        self::assertNotNull($challenge->getConsumedAt());
        self::assertFalse($challenge->isUsable('challenge', AccessPasskeyCeremonyPurpose::Registration, 'example.test', 'https://example.test', $now->modify('+2 minutes')));

        $credential = new AccessPasskeyCredentialEntity($user, 'credential', 'handle', 'public-key', [' internal ', 'internal'], ' Device ', 1, 'record');
        self::assertSame('record', $credential->getCredentialRecord());
        self::assertSame(['internal'], $credential->getTransports());
        $credential->updateCredentialRecord('record-2');
        $credential->advanceSignCount(2);
        $credential->markUsedWithoutCounter();
        $credential->rename('Renamed');
        self::assertSame(2, $credential->getSignCount());
        self::assertNotNull($credential->getLastUsedAt());
        $credential->revoke();
        self::assertFalse($credential->isActive());
    }

    public function testCoverageClosureForRecoverySecuritySessionAndVerificationEntities(): void
    {
        $user = new AccessEntity('entity-contracts@example.test');
        $now = new \DateTimeImmutable();
        $recovery = new AccessRecoveryCodeEntity($user, 'abcdef1234', null);
        self::assertSame('1234', $recovery->getLastFourCharacters());
        self::assertFalse($recovery->isUsed());
        $recovery->markUsed($now);
        self::assertTrue($recovery->isUsed());
        self::assertSame('ABCD', (new AccessRecoveryCodeEntity($user, 'hash-value', 'ABCD'))->getLastFourCharacters());
        self::assertSame('CDEF', (new AccessRecoveryCodeEntity($user, 'abcdef', ''))->getLastFourCharacters());
        $implicitRecovery = new AccessRecoveryCodeEntity();
        self::assertNull($implicitRecovery->getUser());
        $implicitRecovery->consume();
        self::assertTrue($implicitRecovery->isUsed());
        $explicitConsumedRecovery = new AccessRecoveryCodeEntity();
        $explicitConsumedAt = $now->modify('+1 second');
        $explicitConsumedRecovery->consume($explicitConsumedAt);
        self::assertSame($explicitConsumedAt, $explicitConsumedRecovery->getConsumedAt());
        $implicitMarkedRecovery = new AccessRecoveryCodeEntity();
        $implicitMarkedRecovery->markUsed();
        self::assertTrue($implicitMarkedRecovery->isUsed());

        $event = new AccessSecurityEventEntity('not-a-known-event', 'not-a-known-severity', $user, '127.0.0.1', 'agent', ['key' => 'value']);
        self::assertSame(AccessSecurityEventType::SignInFailed, $event->getEventType());
        self::assertSame(AccessSecurityEventSeverity::Info, $event->getSeverity());
        $event->setEventType(AccessSecurityEventType::SignInSucceeded)->setSeverity(AccessSecurityEventSeverity::Warning)->setContext(['severity' => AccessSecurityEventSeverity::Warning->value])->setIpAddress(null)->setUserAgent(null)->setUser(null);
        self::assertNull($event->getUser());
        self::assertSame(AccessSecurityEventSeverity::Warning, $event->getSeverity());
        self::assertSame(AccessSecurityEventSeverity::Info, (new AccessSecurityEventEntity())->getSeverity());
        self::assertSame(AccessSecurityEventType::SignInSucceeded, (new AccessSecurityEventEntity(AccessSecurityEventType::SignInSucceeded))->getEventType());
        self::assertSame(AccessSecurityEventSeverity::Warning, (new AccessSecurityEventEntity(null, AccessSecurityEventSeverity::Warning))->getSeverity());
        $nonStringSeverity = new AccessSecurityEventEntity();
        $nonStringSeverity->setContext(['severity' => ['unexpected']]);
        self::assertSame(AccessSecurityEventSeverity::Info, $nonStringSeverity->getSeverity());

        $session = new AccessSessionEntity($user, 'session-id', '127.0.0.1', 'agent');
        self::assertFalse($session->isTrusted());
        $session->setTrusted(true)->setIpAddress(null)->setUserAgent(null)->touch($now)->setExpiresAt($now->modify('+1 hour'));
        self::assertTrue($session->isTrusted());
        self::assertTrue($session->isActive());
        $session->invalidate($now->modify('+1 minute'));
        self::assertSame($session->getRevokedAt(), $session->getInvalidatedAt());
        self::assertFalse($session->isActive());
        $expiredSession = new AccessSessionEntity($user, 'expired-session');
        $expiredSession->setExpiresAt(new \DateTimeImmutable('-1 minute'));
        self::assertFalse($expiredSession->isActive());
        $anonymousSession = new AccessSessionEntity();
        self::assertNull($anonymousSession->getUser());
        self::assertSame('', $anonymousSession->getSessionIdentifier());
        $anonymousSession->setSessionIdentifier('  trimmed-session  ');
        self::assertSame('trimmed-session', $anonymousSession->getSessionIdentifier());
        $anonymousSession->setSessionIdentifier('   ');
        self::assertSame('', $anonymousSession->getSessionIdentifier());

        $verification = new AccessVerificationChallengeEntity($user, AccessVerificationChallengeType::EmailVerification, 'user@example.test', 'token', $now->modify('+15 minutes'), '127.0.0.1');
        self::assertSame('email', $verification->getChannelType());
        self::assertSame(AccessVerificationChallengeType::EmailVerification, $verification->getChallengeType());
        self::assertSame('token', $verification->getCodeHash());
        self::assertFalse($verification->hasReachedAttemptLimit(1));
        $verification->registerAttempt();
        self::assertTrue($verification->hasReachedAttemptLimit(1));
        $verification->consume($now);
        self::assertTrue($verification->isCompleted());
        $verification->setChannelType('phone')->setTarget('7135550101')->setToken('next')->setExpiresAt($now->modify('+30 minutes'));
        self::assertSame(AccessVerificationChallengeType::PhoneVerification, $verification->getChallengeType());
        foreach ([
            AccessVerificationChallengeType::EmailVerification,
            'email',
            AccessVerificationChallengeType::PhoneVerification,
            'phone',
            AccessVerificationChallengeType::PasswordRecovery,
            'recovery',
            'password_recovery',
            'custom',
        ] as $challengeType) {
            $verification->setChallengeType($challengeType);
            self::assertNotSame('', $verification->getChannelType());
        }
        $freshVerification = new AccessVerificationChallengeEntity();
        $freshVerification->consume();
        self::assertTrue($freshVerification->isCompleted());

        $channelType = new \ReflectionProperty(AccessVerificationChallengeEntity::class, 'channelType');
        $legacyVerification = new AccessVerificationChallengeEntity();
        $channelType->setValue($legacyVerification, 'email_verification');
        self::assertSame(AccessVerificationChallengeType::EmailVerification, $legacyVerification->getChallengeType());
        $channelType->setValue($legacyVerification, 'phone_verification');
        self::assertSame(AccessVerificationChallengeType::PhoneVerification, $legacyVerification->getChallengeType());
        $channelType->setValue($legacyVerification, 'legacy-custom');
        self::assertSame(AccessVerificationChallengeType::PasswordRecovery, $legacyVerification->getChallengeType());
    }

    private static function assertInvalid(callable $callback): void
    {
        try {
            $callback();
            self::fail('Expected InvalidArgumentException was not thrown.');
        } catch (\InvalidArgumentException $exception) {
            self::assertInstanceOf(\InvalidArgumentException::class, $exception);
        }
    }
}
