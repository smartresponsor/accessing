<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Clock\AccessSystemClock;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use App\Accessing\Service\SecondFactor\AccessSecondFactorService;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class AccessSecondFactorServiceCoverageTest extends TestCase
{
    public function testBeginEnrollmentCreatesFactorAndReusesExistingSecret(): void
    {
        $clock = new AccessSystemClock();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist')->with(self::isInstanceOf(AccessSecondFactorEntity::class));
        $entityManager->expects(self::once())->method('flush');
        $service = $this->service($entityManager, $this->createMock(AccessSecurityEventServiceInterface::class), $clock);
        $user = new AccessEntity('begin-factor@example.test');

        $enrollment = $service->beginEnrollment($user);
        self::assertNotSame('', $enrollment->secret);
        self::assertStringContainsString('otpauth://totp/', $enrollment->provisioningUri);
        self::assertSame([], $enrollment->recoveryCodes);
        self::assertSame($enrollment->secret, $user->getSecondFactor()?->getSecret());

        $reuseEntityManager = $this->createMock(EntityManagerInterface::class);
        $reuseEntityManager->expects(self::never())->method('persist');
        $reuseEntityManager->expects(self::never())->method('flush');
        $reuseService = $this->service($reuseEntityManager, $this->createMock(AccessSecurityEventServiceInterface::class), $clock);
        $reused = $reuseService->beginEnrollment($user);
        self::assertSame($enrollment->secret, $reused->secret);
        self::assertStringContainsString('otpauth://totp/', $reused->provisioningUri);
    }

    public function testConfirmEnrollmentHandlesMissingInvalidAndValidCodes(): void
    {
        $clock = new AccessSystemClock();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $service = $this->service($entityManager, $events, $clock);
        self::assertNull($service->confirmEnrollment(new AccessEntity('missing-factor@example.test'), '123456'));

        $user = new AccessEntity('confirm-factor@example.test');
        $secret = TOTP::create(clock: $clock)->getSecret();
        $factor = new AccessSecondFactorEntity($user, $secret, $user->getEmailAddress());
        $user->setSecondFactor($factor);
        self::assertNull($service->confirmEnrollment($user, ''));
        self::assertNull($service->confirmEnrollment($user, '000000'));

        $validEntityManager = $this->createMock(EntityManagerInterface::class);
        $validEntityManager->expects(self::once())->method('flush');
        $validEvents = $this->createMock(AccessSecurityEventServiceInterface::class);
        $validEvents->expects(self::once())->method('record')->with(
            AccessSecurityEventType::SecondFactorEnrolled,
            AccessSecurityEventSeverity::Info,
            $user,
        );
        $validService = $this->service($validEntityManager, $validEvents, $clock);
        $validCode = TOTP::create($secret, clock: $clock)->now();
        $confirmed = $validService->confirmEnrollment($user, $validCode);

        self::assertNotNull($confirmed);
        self::assertTrue($factor->isEnabled());
        self::assertCount(8, $confirmed->recoveryCodes);
        self::assertCount(8, $user->getRecoveryCodes());
        foreach ($confirmed->recoveryCodes as $plainCode) {
            self::assertMatchesRegularExpression('/^[A-F0-9]{10}$/', $plainCode);
        }
    }

    public function testVerifyChallengeAcceptsRecoveryCodeAndSkipsUsedCodes(): void
    {
        $clock = new AccessSystemClock();
        $user = new AccessEntity('recovery-factor@example.test');
        $secret = TOTP::create(clock: $clock)->getSecret();
        $factor = new AccessSecondFactorEntity($user, $secret, $user->getEmailAddress());
        $factor->confirm();
        $user->setSecondFactor($factor);

        $used = new AccessRecoveryCodeEntity($user, hash_hmac('sha256', 'USED1111', 'test-secret'), '1111');
        $used->markUsed();
        $user->addRecoveryCode($used);
        $active = new AccessRecoveryCodeEntity($user, hash_hmac('sha256', 'ABCD1234', 'test-secret'), '1234');
        $user->addRecoveryCode($active);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::RecoveryCodeUsed,
            AccessSecurityEventSeverity::Warning,
            $user,
        );

        $service = $this->service($entityManager, $events, $clock);
        self::assertTrue($service->verifyChallenge($user, 'abcd-1234'));
        self::assertTrue($active->isUsed());
    }

    public function testVerifyChallengeRejectsUserWithoutEnabledSecondFactor(): void
    {
        $service = $this->service(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
            new AccessSystemClock(),
        );

        self::assertFalse($service->verifyChallenge(new AccessEntity('no-factor@example.test'), '123456'));
    }

    public function testConfirmEnrollmentRemovesExistingRecoveryCodesBeforeRegeneration(): void
    {
        $clock = new AccessSystemClock();
        $user = new AccessEntity('replace-recovery@example.test');
        $secret = TOTP::create(clock: $clock)->getSecret();
        $factor = new AccessSecondFactorEntity($user, $secret, $user->getEmailAddress());
        $user->setSecondFactor($factor);
        $old = new AccessRecoveryCodeEntity($user, 'old-hash', '0001');
        $user->addRecoveryCode($old);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($old);
        $entityManager->expects(self::once())->method('flush');
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record');

        $confirmed = $this->service($entityManager, $events, $clock)->confirmEnrollment(
            $user,
            TOTP::create($secret, clock: $clock)->now(),
        );

        self::assertNotNull($confirmed);
        self::assertCount(8, $confirmed->recoveryCodes);
    }

    public function testDisableSecondFactorWithoutEnrollmentStillFlushesAndRecordsAuditEvent(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::once())->method('flush');
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::SecondFactorRevoked,
            AccessSecurityEventSeverity::Warning,
            self::isInstanceOf(AccessEntity::class),
        );

        $this->service($entityManager, $events, new AccessSystemClock())->disableSecondFactor(
            new AccessEntity('disable-without-factor@example.test'),
        );
    }

    public function testBeginEnrollmentUsesFallbackLabelWhenEmailIsEmpty(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');
        $enrollment = $this->service(
            $entityManager,
            $this->createMock(AccessSecurityEventServiceInterface::class),
            new AccessSystemClock(),
        )->beginEnrollment(new AccessEntity());

        self::assertStringContainsString('accessing', urldecode($enrollment->provisioningUri));
    }

    public function testDisableSecondFactorRevokesAndRemovesRecoveryCodes(): void
    {
        $clock = new AccessSystemClock();
        $user = new AccessEntity('disable-factor@example.test');
        $factor = new AccessSecondFactorEntity($user, TOTP::create(clock: $clock)->getSecret(), $user->getEmailAddress());
        $factor->confirm();
        $user->setSecondFactor($factor);
        $first = new AccessRecoveryCodeEntity($user, 'hash-1', '0001');
        $second = new AccessRecoveryCodeEntity($user, 'hash-2', '0002');
        $user->addRecoveryCode($first)->addRecoveryCode($second);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))->method('remove')->with(self::isInstanceOf(AccessRecoveryCodeEntity::class));
        $entityManager->expects(self::once())->method('flush');
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::SecondFactorRevoked,
            AccessSecurityEventSeverity::Warning,
            $user,
        );

        $this->service($entityManager, $events, $clock)->disableSecondFactor($user);
        self::assertFalse($factor->isEnabled());
    }

    public function testBlankExistingSecretIsRejected(): void
    {
        $clock = new AccessSystemClock();
        $user = new AccessEntity('blank-secret@example.test');
        $user->setSecondFactor(new AccessSecondFactorEntity($user, '   ', $user->getEmailAddress()));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Second-factor secret must not be empty.');
        $this->service(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
            $clock,
        )->beginEnrollment($user);
    }

    private function service(
        EntityManagerInterface $entityManager,
        AccessSecurityEventServiceInterface $events,
        AccessSystemClock $clock,
    ): AccessSecondFactorService {
        return new AccessSecondFactorService(
            $entityManager,
            $events,
            new RateLimiterFactory([
                'id' => 'second_factor_coverage',
                'policy' => 'sliding_window',
                'limit' => 10,
                'interval' => '15 minutes',
            ], new InMemoryStorage()),
            $clock,
            'test-secret',
        );
    }
}
