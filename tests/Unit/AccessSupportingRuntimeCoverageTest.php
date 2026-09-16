<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Authenticator\AccessAuthenticationEntryPoint;
use App\Accessing\Contract\AccessIntegrationContract;
use App\Accessing\DTO\AccessExternalIdentityProfileDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\DTO\Config\AccessEnvironmentConfigDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessExternalIdentityEntity;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\Factory\Surface\AccessHomeSurfaceContractFactory;
use App\Accessing\Provider\Configuration\AccessConfigurationToolProvider;
use App\Accessing\RepositoryInterface\AccessExternalIdentityRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\Service\Config\AccessEnvironmentConfigService;
use App\Accessing\Service\OAuth\AccessExternalAuthenticationService;
use App\Accessing\Service\OAuth\AccessGoogleOAuthService;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
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
        $this->expectException(\InvalidArgumentException::class);
        new AccessExternalIdentityProfileDTO('', 'subject', 'mail@example.test', false);
    }
}
