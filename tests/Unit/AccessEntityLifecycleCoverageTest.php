<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobileSessionEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\Entity\AccessSessionEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\ValueObject\AccessVerificationChallengeType;
use PHPUnit\Framework\TestCase;

final class AccessEntityLifecycleCoverageTest extends TestCase
{
    public function testCredentialAndSecondFactorBidirectionalLifecycle(): void
    {
        $user = new AccessEntity('USER@example.test', ' User Name ');

        self::assertSame('user@example.test', $user->getEmail());
        self::assertSame('user@example.test', $user->getEmailAddress());
        self::assertSame('user@example.test', $user->getUserIdentifier());
        self::assertSame('User Name', $user->getDisplayName());
        self::assertContains('ROLE_USER', $user->getRoles());

        $credential = new AccessCredentialEntity($user, 'hash-one');
        self::assertNull($credential->getId());
        self::assertSame($user, $credential->getUser());
        self::assertSame('hash-one', $credential->getPasswordHash());
        $changedAt = $credential->getPasswordChangedAt();

        $user->setCredential($credential);
        self::assertSame($credential, $user->getCredential());
        self::assertSame('hash-one', $user->getPassword());
        self::assertSame('hash-one', $user->getPasswordHash());

        $secondUser = new AccessEntity('second@example.test');
        $credential->setUser($secondUser);
        self::assertSame($secondUser, $credential->getUser());
        self::assertSame($credential, $secondUser->getCredential());

        usleep(1000);
        $credential->updatePasswordHash('hash-two');
        self::assertSame('hash-two', $credential->getPasswordHash());
        self::assertGreaterThanOrEqual($changedAt, $credential->getPasswordChangedAt());

        $secondFactor = new AccessSecondFactorEntity($user, 'secret-value', 'Primary TOTP');
        self::assertNull($secondFactor->getId());
        self::assertSame($user, $secondFactor->getUser());
        self::assertSame('secret-value', $secondFactor->getSecret());
        self::assertSame('Primary TOTP', $secondFactor->getLabel());
        self::assertInstanceOf(\DateTimeImmutable::class, $secondFactor->getCreatedAt());
        self::assertNull($secondFactor->getConfirmedAt());
        self::assertNull($secondFactor->getLastUsedAt());
        self::assertFalse($secondFactor->isEnabled());

        $user->setSecondFactor($secondFactor);
        self::assertSame($secondFactor, $user->getSecondFactor());
        self::assertSame('secret-value', $user->getTotpSecret());
        self::assertFalse($user->isSecondFactorEnabled());

        $secondFactor->confirm();
        self::assertInstanceOf(\DateTimeImmutable::class, $secondFactor->getConfirmedAt());
        self::assertTrue($secondFactor->isEnabled());
        self::assertTrue($user->isSecondFactorEnabled());

        $secondFactor->markUsed();
        self::assertInstanceOf(\DateTimeImmutable::class, $secondFactor->getLastUsedAt());
        $secondFactor->revoke();
        self::assertFalse($secondFactor->isEnabled());

        $secondFactor->setUser($secondUser);
        self::assertSame($secondFactor, $secondUser->getSecondFactor());
    }

    public function testSessionLifecycleCoversTrustExpiryTouchAndInvalidation(): void
    {
        $user = new AccessEntity('session@example.test');
        $session = new AccessSessionEntity($user, ' session-id ', '127.0.0.1', 'agent');

        self::assertNull($session->getId());
        self::assertSame($user, $session->getUser());
        self::assertSame('session-id', $session->getSessionIdentifier());
        self::assertSame('127.0.0.1', $session->getIpAddress());
        self::assertSame('agent', $session->getUserAgent());
        self::assertFalse($session->isTrusted());
        self::assertSame($session->getCreatedAt(), $session->getIssuedAt());
        self::assertTrue($session->isActive());

        $lastSeen = new \DateTimeImmutable('2026-01-01T12:00:00+00:00');
        $expires = new \DateTimeImmutable('+2 hours');
        $session
            ->setSessionIdentifier(' updated-id ')
            ->setIpAddress('10.0.0.1')
            ->setUserAgent('updated-agent')
            ->setTrusted(true)
            ->touch($lastSeen)
            ->setExpiresAt($expires);

        self::assertSame('updated-id', $session->getSessionIdentifier());
        self::assertSame('10.0.0.1', $session->getIpAddress());
        self::assertSame('updated-agent', $session->getUserAgent());
        self::assertTrue($session->isTrusted());
        self::assertSame($lastSeen, $session->getLastSeenAt());
        self::assertSame($expires, $session->getExpiresAt());
        self::assertNull($session->getRevokedAt());
        self::assertNull($session->getInvalidatedAt());

        $revokedAt = new \DateTimeImmutable('2026-01-02T12:00:00+00:00');
        $session->invalidate($revokedAt);
        self::assertSame($revokedAt, $session->getRevokedAt());
        self::assertSame($revokedAt, $session->getInvalidatedAt());
        self::assertFalse($session->isActive());

        $expired = new AccessSessionEntity($user, 'expired');
        $expired->setExpiresAt(new \DateTimeImmutable('-1 minute'));
        self::assertFalse($expired->isActive());

        $user->addUserSession($session);
        $user->addUserSession($session);
        self::assertTrue($user->getUserSessions()->contains($session));
    }

    public function testVerificationChallengeNormalizesTypesAndTracksAttempts(): void
    {
        $user = new AccessEntity('verify@example.test');
        $expires = new \DateTimeImmutable('+10 minutes');
        $challenge = new AccessVerificationChallengeEntity(
            $user,
            AccessVerificationChallengeType::EmailVerification,
            ' target@example.test ',
            ' token-value ',
            $expires,
            '203.0.113.5',
        );

        self::assertNull($challenge->getId());
        self::assertSame($user, $challenge->getUser());
        self::assertSame(AccessVerificationChallengeType::EmailVerification, $challenge->getChallengeType());
        self::assertSame('email', $challenge->getChannelType());
        self::assertSame('target@example.test', $challenge->getTarget());
        self::assertSame('token-value', $challenge->getToken());
        self::assertSame('token-value', $challenge->getCodeHash());
        self::assertSame($expires, $challenge->getExpiresAt());
        self::assertSame('203.0.113.5', $challenge->getRequestedIpAddress());
        self::assertSame($challenge->getCreatedAt(), $challenge->getRequestedAt());
        self::assertFalse($challenge->isCompleted());
        self::assertNull($challenge->getCompletedAt());
        self::assertNull($challenge->getConsumedAt());

        $challenge->setChannelType('phone_verification');
        self::assertSame(AccessVerificationChallengeType::PhoneVerification, $challenge->getChallengeType());
        $challenge->setChallengeType('recovery');
        self::assertSame(AccessVerificationChallengeType::PasswordRecovery, $challenge->getChallengeType());
        $challenge->setChallengeType('something-custom');
        self::assertSame(AccessVerificationChallengeType::PasswordRecovery, $challenge->getChallengeType());

        $challenge->setTarget(' changed@example.test ')->setToken(' new-token ');
        self::assertSame('changed@example.test', $challenge->getTarget());
        self::assertSame('new-token', $challenge->getToken());

        for ($i = 0; $i < 5; ++$i) {
            $challenge->registerAttempt();
        }
        self::assertSame(5, $challenge->getAttemptCount());
        self::assertTrue($challenge->hasReachedAttemptLimit());
        self::assertTrue($challenge->hasReachedAttemptLimit(4));
        self::assertFalse($challenge->hasReachedAttemptLimit(6));

        $completedAt = new \DateTimeImmutable('2026-01-03T12:00:00+00:00');
        $challenge->consume($completedAt);
        self::assertTrue($challenge->isCompleted());
        self::assertSame($completedAt, $challenge->getCompletedAt());
        self::assertSame($completedAt, $challenge->getConsumedAt());

        $replacementExpiry = new \DateTimeImmutable('+20 minutes');
        $challenge->setExpiresAt($replacementExpiry);
        self::assertSame($replacementExpiry, $challenge->getExpiresAt());

        $user->addVerificationChallenge($challenge);
        $user->addVerificationChallenge($challenge);
        self::assertTrue($user->getVerificationChallenges()->contains($challenge));
    }

    public function testSecurityEventDefaultsAndMutationSemantics(): void
    {
        $user = new AccessEntity('security@example.test');
        $event = new AccessSecurityEventEntity(
            AccessSecurityEventType::SignInSucceeded,
            AccessSecurityEventSeverity::Warning,
            $user,
            '198.51.100.10',
            'ua',
            ['source' => 'unit'],
        );

        self::assertNull($event->getId());
        self::assertSame($user, $event->getUser());
        self::assertSame(AccessSecurityEventType::SignInSucceeded, $event->getEventType());
        self::assertSame(AccessSecurityEventSeverity::Warning, $event->getSeverity());
        self::assertSame('198.51.100.10', $event->getIpAddress());
        self::assertSame('ua', $event->getUserAgent());
        self::assertSame('unit', $event->getContext()['source']);
        self::assertInstanceOf(\DateTimeImmutable::class, $event->getOccurredAt());

        $event
            ->setUser(null)
            ->setEventType(' unknown-type ')
            ->setSeverity(' invalid-severity ')
            ->setContext(['severity' => 'critical', 'step' => 2])
            ->setIpAddress(null)
            ->setUserAgent(null);

        self::assertNull($event->getUser());
        self::assertSame(AccessSecurityEventType::SignInFailed, $event->getEventType());
        self::assertSame(AccessSecurityEventSeverity::Critical, $event->getSeverity());
        self::assertSame(['severity' => 'critical', 'step' => 2], $event->getContext());
        self::assertNull($event->getIpAddress());
        self::assertNull($event->getUserAgent());

        $default = new AccessSecurityEventEntity();
        self::assertSame(AccessSecurityEventType::SignInFailed, $default->getEventType());
        self::assertSame(AccessSecurityEventSeverity::Info, $default->getSeverity());
    }

    public function testAccessEntitySecurityAndProfileLifecycle(): void
    {
        $user = new AccessEntity();
        self::assertSame('user', $user->getUserIdentifier());
        self::assertNull($user->getId());
        self::assertNull($user->getPhoneNumber());
        self::assertFalse($user->isEmailVerified());
        self::assertFalse($user->isPhoneVerified());
        self::assertFalse($user->isLocked());
        self::assertSame(0, $user->getFailedLoginCount());
        self::assertSame(0, $user->getFailedSignInCount());
        self::assertNull($user->getLastSignInAt());

        $user
            ->setEmail(' PROFILE@EXAMPLE.TEST ')
            ->setDisplayName(null)
            ->setRoles(['ROLE_ADMIN', 'ROLE_ADMIN'])
            ->setPhoneNumber(' +1 713 555 0101 ');

        self::assertSame('profile@example.test', $user->getEmail());
        self::assertNull($user->getDisplayName());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertSame('+1 713 555 0101', $user->getPhoneNumber());

        $user->changePhoneNumber(null);
        self::assertNull($user->getPhoneNumber());

        $emailVerifiedAt = new \DateTimeImmutable('2026-02-01T00:00:00+00:00');
        $phoneVerifiedAt = new \DateTimeImmutable('2026-02-02T00:00:00+00:00');
        $user->markEmailVerified($emailVerifiedAt)->markPhoneVerified($phoneVerifiedAt);
        self::assertTrue($user->isEmailVerified());
        self::assertSame($emailVerifiedAt, $user->getEmailVerifiedAt());
        self::assertTrue($user->isPhoneVerified());
        self::assertSame($phoneVerifiedAt, $user->getPhoneVerifiedAt());

        $user->increaseFailedLoginCount()->registerFailedSignInAttempt();
        self::assertSame(2, $user->getFailedLoginCount());
        self::assertSame(2, $user->getFailedSignInCount());
        $user->lock();
        self::assertTrue($user->isLocked());

        $futureLock = new \DateTimeImmutable('+10 minutes');
        $user->lockUntil($futureLock);
        self::assertTrue($user->isLocked());
        self::assertSame($futureLock, $user->getLockedUntil());

        $user->unlock();
        self::assertFalse($user->isLocked());
        self::assertNull($user->getLockedUntil());
        self::assertSame(0, $user->getFailedLoginCount());

        $user->increaseFailedLoginCount()->resetFailedLoginCount();
        self::assertSame(0, $user->getFailedLoginCount());
        $user->increaseFailedLoginCount()->markSuccessfulSignIn();
        self::assertSame(0, $user->getFailedLoginCount());
        self::assertFalse($user->isLocked());
        self::assertInstanceOf(\DateTimeImmutable::class, $user->getLastSignInAt());
        self::assertSame($user->getCreatedAt(), $user->getRegisteredAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());

        $user->lockUntil(new \DateTimeImmutable('-1 minute'));
        self::assertFalse($user->isLocked());
        self::assertNull($user->getLockedUntil());

        self::assertCount(0, $user->getRecoveryCodes());
        $user->eraseCredentials();
    }

    public function testMobileSessionRotationReuseAndValidationBranches(): void
    {
        $user = new AccessEntity('mobile@example.test');
        $now = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');
        $accessExpiry = $now->modify('+15 minutes');
        $refreshExpiry = $now->modify('+30 days');
        $session = new AccessMobileSessionEntity($user, 'session-1', 'access-1', 'refresh-1', 'iPhone', $now, $accessExpiry, $refreshExpiry);

        self::assertNull($session->getId());
        self::assertSame($user, $session->getUser());
        self::assertSame('session-1', $session->getSessionId());
        self::assertSame('iPhone', $session->getDeviceName());
        self::assertSame($now, $session->getCreatedAt());
        self::assertSame($accessExpiry, $session->getAccessExpiresAt());
        self::assertSame($refreshExpiry, $session->getRefreshExpiresAt());
        self::assertTrue($session->hasAccessToken('access-1'));
        self::assertFalse($session->hasAccessToken('wrong'));
        self::assertTrue($session->hasRefreshToken('refresh-1'));
        self::assertFalse($session->hasPreviousRefreshToken('refresh-1'));
        self::assertTrue($session->isAccessActive($now));
        self::assertTrue($session->isRefreshActive($now));

        $nextAccessExpiry = $now->modify('+20 minutes');
        $nextRefreshExpiry = $now->modify('+31 days');
        $session->rotate('access-2', 'refresh-2', $now, $nextAccessExpiry, $nextRefreshExpiry);
        self::assertTrue($session->hasAccessToken('access-2'));
        self::assertTrue($session->hasRefreshToken('refresh-2'));
        self::assertTrue($session->hasPreviousRefreshToken('refresh-1'));

        $session->markRefreshReuseDetected($now);
        self::assertFalse($session->isAccessActive($now));
        self::assertFalse($session->isRefreshActive($now));

        $this->expectException(\DomainException::class);
        $session->rotate('access-3', 'refresh-3', $now, $nextAccessExpiry, $nextRefreshExpiry);
    }
}
