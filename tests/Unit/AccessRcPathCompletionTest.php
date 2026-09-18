<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Contract\Surface\AccessHomeSurfaceContract;
use App\Accessing\DTO\AccessExternalIdentityProfileDTO;
use App\Accessing\DTO\AccessPasskeyAssertionResultDTO;
use App\Accessing\DTO\AccessPasskeyAttestationResultDTO;
use App\Accessing\DTO\AccessPasskeyAuthenticationOptionsDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessExternalIdentityEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\Entity\AccessMobileSessionEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessSessionEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\Policy\Lifecycle\AccessLifecyclePolicy;
use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;
use App\Accessing\Service\Passkey\AccessPasskeyChallengeService;
use App\Accessing\Service\Passkey\AccessPasskeyRegistrationService;
use App\Accessing\Service\PhoneVerification\AccessPhoneVerificationGatewayService;
use App\Accessing\Service\SecurityNotification\AccessSecurityNotificationService;
use App\Accessing\ValueObject\AccessEmailAddress;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessVerificationChallengeType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

final class AccessRcPathCompletionTest extends TestCase
{
    public function testSmallValueAndPolicyPathsAreComplete(): void
    {
        $email = new AccessEmailAddress(' Example@Example.Test ');
        self::assertSame('example@example.test', (string) $email);

        foreach (['', '   ', 'not-an-email'] as $invalid) {
            try {
                new AccessEmailAddress($invalid);
                self::fail('Invalid email must be rejected.');
            } catch (\InvalidArgumentException) {
            }
        }

        self::assertFalse(AccessLifecyclePolicy::canTransition('unknown', 'active'));
        self::assertTrue(AccessLifecyclePolicy::canTransition('deleted', 'deleted'));

        $surface = new AccessHomeSurfaceContract('access', 'access.overview', 'access/index.html.twig', [], [
            'accessingProductName' => ['not-scalar'],
            'user' => null,
            'events' => 'not-array',
        ]);
        $context = $surface->toTemplateContext();
        self::assertSame('Accessing', $context['accessingProductName']);
        self::assertSame([], $context['events']);
    }

    public function testEntityConstructorAndFallbackPathsAreComplete(): void
    {
        $now = new \DateTimeImmutable('2026-09-17T12:00:00+00:00');
        $user = new AccessEntity('paths@example.test');

        foreach ([['', 'Device'], ['token', '']] as [$token, $device]) {
            try {
                new AccessMobilePendingAuthEntity($user, $token, AccessMobilePendingPurpose::EmailVerification, $device, $now, $now->modify('+10 minutes'));
                self::fail('Blank mobile pending values must be rejected.');
            } catch (\InvalidArgumentException) {
            }
        }

        foreach ([
            ['', 'access', 'refresh', 'Device'],
            ['session', '', 'refresh', 'Device'],
            ['session', 'access', '', 'Device'],
            ['session', 'access', 'refresh', ''],
        ] as [$sessionId, $access, $refresh, $device]) {
            try {
                new AccessMobileSessionEntity($user, $sessionId, $access, $refresh, $device, $now, $now->modify('+10 minutes'), $now->modify('+20 minutes'));
                self::fail('Blank mobile session values must be rejected.');
            } catch (\InvalidArgumentException) {
            }
        }

        $onlyUser = new AccessRecoveryCodeEntity($user);
        $onlyHash = new AccessRecoveryCodeEntity(null, 'hash-only');
        self::assertSame($user, $onlyUser->getUser());
        self::assertSame('hash-only', $onlyHash->getCodeHash());
        $onlyHash->markUsed();
        self::assertTrue($onlyHash->isUsed());

        $eventTypeOnly = new AccessSecurityEventEntity('custom-event');
        self::assertSame(\App\Accessing\ValueObject\AccessSecurityEventType::SignInFailed, $eventTypeOnly->getEventType());
        $severityOnly = new AccessSecurityEventEntity(null, AccessSecurityEventSeverity::Warning);
        self::assertSame(AccessSecurityEventSeverity::Warning, $severityOnly->getSeverity());
        $severityOnly->setSeverity('bogus');
        self::assertSame(AccessSecurityEventSeverity::Info, $severityOnly->getSeverity());

        $emptySession = new AccessSessionEntity();
        $userOnlySession = new AccessSessionEntity($user);
        $idOnlySession = new AccessSessionEntity(null, 'id-only');
        $emptySession->setSessionIdentifier('');
        self::assertSame('', $emptySession->getSessionIdentifier());
        self::assertNull($emptySession->getUser());
        self::assertSame($user, $userOnlySession->getUser());
        self::assertSame('id-only', $idOnlySession->getSessionIdentifier());
        $emptySession->touch();
        $emptySession->revoke();
        self::assertNotNull($emptySession->getRevokedAt());

        $challenge = new AccessVerificationChallengeEntity();
        foreach ([
            AccessVerificationChallengeType::PhoneVerification,
            AccessVerificationChallengeType::PasswordRecovery,
            'email_verification',
            'password_recovery',
        ] as $type) {
            $challenge->setChallengeType($type);
            self::assertInstanceOf(AccessVerificationChallengeType::class, $challenge->getChallengeType());
        }
        $challenge->markCompleted();
        self::assertTrue($challenge->isCompleted());
    }

    public function testDtoValidationMatricesCoverAllFiniteConstructorPaths(): void
    {
        self::assertSame('provider', (new AccessExternalIdentityProfileDTO('provider', 'subject', 'mail@example.test', true))->provider);
        foreach ([
            ['', 'subject', 'mail@example.test'],
            ['provider', '', 'mail@example.test'],
            ['provider', 'subject', ''],
        ] as [$provider, $subject, $email]) {
            try {
                new AccessExternalIdentityProfileDTO($provider, $subject, $email, false);
                self::fail('Incomplete external identity profile must be rejected.');
            } catch (\InvalidArgumentException) {
            }
        }

        self::assertSame(1, (new AccessPasskeyAssertionResultDTO('credential', 'handle', 1))->signCount);
        foreach ([
            ['', 'handle', 0],
            ['credential', '', 0],
            ['credential', 'handle', -1],
        ] as [$credentialId, $userHandle, $signCount]) {
            try {
                new AccessPasskeyAssertionResultDTO($credentialId, $userHandle, $signCount);
                self::fail('Invalid assertion result must be rejected.');
            } catch (\InvalidArgumentException) {
            }
        }

        self::assertSame(0, (new AccessPasskeyAttestationResultDTO('credential', 'handle', 'key', [], 0))->signCount);
        foreach ([
            ['', 'handle', 'key', 0],
            ['credential', '', 'key', 0],
            ['credential', 'handle', '', 0],
            ['credential', 'handle', 'key', -1],
        ] as [$credentialId, $userHandle, $publicKey, $signCount]) {
            try {
                new AccessPasskeyAttestationResultDTO($credentialId, $userHandle, $publicKey, [], $signCount);
                self::fail('Invalid attestation result must be rejected.');
            } catch (\InvalidArgumentException) {
            }
        }

        $options = new AccessPasskeyAuthenticationOptionsDTO('challenge', 'example.test', []);
        self::assertSame('challenge', $options->toArray()['publicKey']['challenge']);
        foreach ([['', 'example.test'], ['challenge', '']] as [$challenge, $rpId]) {
            try {
                new AccessPasskeyAuthenticationOptionsDTO($challenge, $rpId, []);
                self::fail('Invalid authentication options must be rejected.');
            } catch (\InvalidArgumentException) {
            }
        }
    }

    public function testPhoneGatewayCoversSelfUnsupportedSupportedAndMissingProviders(): void
    {
        $supported = $this->createMock(AccessPhoneVerificationProviderInterface::class);
        $supported->method('supports')->with('fake')->willReturn(true);
        $supported->expects(self::exactly(2))->method('sendVerificationMessage')->with('+15555550123', 'message');
        $unsupported = $this->createMock(AccessPhoneVerificationProviderInterface::class);
        $unsupported->method('supports')->willReturn(false);

        $gateway = new AccessPhoneVerificationGatewayService([$unsupported, $supported], 'fake');
        self::assertTrue($gateway->supports('fake'));
        self::assertFalse($gateway->supports('other'));
        $gateway->sendVerificationMessage('+15555550123', 'message');

        $providers = new \ArrayObject();
        $selfAware = new AccessPhoneVerificationGatewayService($providers, 'fake');
        $providers->append($selfAware);
        $providers->append($unsupported);
        $providers->append($supported);
        $selfAware->sendVerificationMessage('+15555550123', 'message');

        $missing = new AccessPhoneVerificationGatewayService([$unsupported], 'missing');
        $this->expectException(\RuntimeException::class);
        $missing->sendVerificationMessage('+15555550123', 'message');
    }

    public function testFiniteDomainStateMatricesCoverEveryShortCircuitOutcome(): void
    {
        self::assertTrue(AccessLifecyclePolicy::canTransition('registered', 'registered'));
        self::assertTrue(AccessLifecyclePolicy::canTransition('registered', 'verified'));
        self::assertFalse(AccessLifecyclePolicy::canTransition('registered', 'deleted'));
        self::assertFalse(AccessLifecyclePolicy::canTransition('unknown', 'active'));

        $now = new \DateTimeImmutable('2026-09-17T12:00:00+00:00');
        $user = new AccessEntity('matrix@example.test');

        $pending = new AccessMobilePendingAuthEntity(
            $user,
            'pending-token',
            AccessMobilePendingPurpose::EmailVerification,
            'Matrix Device',
            $now,
            $now->modify('+10 minutes'),
        );
        self::assertTrue($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now));
        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::SecondFactor, $now));
        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now->modify('+11 minutes')));
        $pending->consume($now->modify('+1 minute'));
        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now->modify('+2 minutes')));

        $mobile = static fn (): AccessMobileSessionEntity => new AccessMobileSessionEntity(
            $user,
            'session-id',
            'access-token',
            'refresh-token',
            'Matrix Device',
            $now,
            $now->modify('+10 minutes'),
            $now->modify('+20 minutes'),
        );
        self::assertTrue($mobile()->isRefreshActive($now));
        self::assertFalse($mobile()->isRefreshActive($now->modify('+21 minutes')));
        $reused = $mobile();
        $reused->markRefreshReuseDetected($now->modify('+1 minute'));
        self::assertFalse($reused->isRefreshActive($now->modify('+2 minutes')));
        $revoked = $mobile();
        $revoked->revoke($now->modify('+1 minute'));
        self::assertFalse($revoked->isRefreshActive($now->modify('+2 minutes')));

        self::assertSame('ABCD', (new AccessRecoveryCodeEntity($user, 'hash-value', 'ABCD'))->getLastFourCharacters());
        self::assertSame('EF12', (new AccessRecoveryCodeEntity($user, 'abcdef12'))->getLastFourCharacters());
        self::assertSame('EF12', (new AccessRecoveryCodeEntity($user, 'abcdef12', ''))->getLastFourCharacters());

        $event = new AccessSecurityEventEntity();
        self::assertSame(AccessSecurityEventSeverity::Info, $event->getSeverity());
        $event->setSeverity(AccessSecurityEventSeverity::Warning);
        self::assertSame(AccessSecurityEventSeverity::Warning, $event->getSeverity());
        $event->setSeverity('bogus');
        self::assertSame(AccessSecurityEventSeverity::Info, $event->getSeverity());
        $event->setEventType(\App\Accessing\ValueObject\AccessSecurityEventType::SignInSucceeded);
        self::assertSame(\App\Accessing\ValueObject\AccessSecurityEventType::SignInSucceeded, $event->getEventType());
        $event->setEventType('custom-event');
        self::assertSame(\App\Accessing\ValueObject\AccessSecurityEventType::SignInFailed, $event->getEventType());

        $external = new AccessExternalIdentityEntity($user, 'google', 'subject', 'matrix@example.test', true);
        self::assertNull($external->getDisplayName());
        $external->recordAuthentication('matrix@example.test', true, '   ', '  ', new \DateTimeImmutable());
        self::assertNull($external->getDisplayName());
        self::assertNull($external->getAvatarUrl());
        $external->recordAuthentication('matrix@example.test', true, ' Matrix User ', ' https://example.test/avatar ', null);
        self::assertSame('Matrix User', $external->getDisplayName());
        self::assertSame('https://example.test/avatar', $external->getAvatarUrl());

        $challenge = static fn (): AccessPasskeyChallengeEntity => new AccessPasskeyChallengeEntity(
            'challenge-token',
            AccessPasskeyCeremonyPurpose::Authentication,
            'example.test',
            'https://example.test',
            $now,
            $now->modify('+10 minutes'),
        );
        self::assertTrue($challenge()->isUsable('challenge-token', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://example.test', $now));
        self::assertFalse($challenge()->isUsable('wrong-token', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://example.test', $now));
        self::assertFalse($challenge()->isUsable('challenge-token', AccessPasskeyCeremonyPurpose::Registration, 'example.test', 'https://example.test', $now));
        self::assertFalse($challenge()->isUsable('challenge-token', AccessPasskeyCeremonyPurpose::Authentication, 'other.test', 'https://example.test', $now));
        self::assertFalse($challenge()->isUsable('challenge-token', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://other.test', $now));
        self::assertFalse($challenge()->isUsable('challenge-token', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://example.test', $now->modify('+11 minutes')));
        $consumed = $challenge();
        $consumed->consume($now->modify('+1 minute'));
        self::assertFalse($consumed->isUsable('challenge-token', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://example.test', $now->modify('+2 minutes')));
    }

    public function testPasskeyEncodingHelpersCoverDeterministicUrlSafeVariants(): void
    {
        $challengeReflection = new \ReflectionClass(AccessPasskeyChallengeService::class);
        $challengeService = $challengeReflection->newInstanceWithoutConstructor();
        $encode = $challengeReflection->getMethod('base64UrlEncode');
        self::assertSame('YWJj', $encode->invoke($challengeService, 'abc'));
        self::assertSame('-_8', $encode->invoke($challengeService, "\xFB\xFF"));

        $registrationReflection = new \ReflectionClass(AccessPasskeyRegistrationService::class);
        $userHandle = $registrationReflection->getMethod('userHandle');
        for ($i = 0; $i < 64; ++$i) {
            $handle = $userHandle->invoke(null, new AccessEntity(sprintf('handle-%d@example.test', $i)));
            self::assertIsString($handle);
            self::assertStringNotContainsString('=', $handle);
        }
    }

    public function testSecurityNotificationResetLinkCoversNamedAndAnonymousGreeting(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::exactly(3))->method('send')->with(self::isInstanceOf(RawMessage::class));
        $service = new AccessSecurityNotificationService($mailer, 'Accessing', 'no-reply@example.test');

        $service->sendPasswordResetLink(
            new AccessEntity('named@example.test', 'Named User'),
            'https://example.test/reset/named',
            new \DateTimeImmutable('2026-09-17T13:00:00+00:00'),
        );
        $service->sendPasswordResetLink(
            new AccessEntity('anonymous@example.test'),
            'https://example.test/reset/anonymous',
            new \DateTimeImmutable('2026-09-17T13:00:00+00:00'),
        );
        $whitespaceUser = new AccessEntity('whitespace@example.test');
        $displayName = new \ReflectionProperty(AccessEntity::class, 'displayName');
        $displayName->setValue($whitespaceUser, '   ');
        $service->sendPasswordResetLink(
            $whitespaceUser,
            'https://example.test/reset/whitespace',
            new \DateTimeImmutable('2026-09-17T13:00:00+00:00'),
        );
    }
}
