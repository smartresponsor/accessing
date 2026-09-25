<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessVerificationChallengeRepositoryInterface;
use App\Accessing\Service\Verification\AccessVerificationChallengeService;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\SecurityNotification\AccessSecurityNotificationServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use App\Accessing\ValueObject\AccessVerificationChallengeType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class AccessVerificationChallengeServiceCoverageTest extends TestCase
{
    public function testIssueAndCompleteEmailPhoneAndRecoveryFlows(): void
    {
        $latest = [];
        $repository = $this->createMock(AccessVerificationChallengeRepositoryInterface::class);
        $repository->method('save')->willReturnCallback(
            static function (AccessVerificationChallengeEntity $challenge) use (&$latest): void {
                $latest[$challenge->getChallengeType()->value] = $challenge;
            },
        );
        $repository->method('findLatestActiveForUser')->willReturnCallback(
            static function (AccessEntity $user, AccessVerificationChallengeType $type) use (&$latest): ?AccessVerificationChallengeEntity {
                return $latest[$type->value] ?? null;
            },
        );
        $repository->expects(self::once())
            ->method('cleanupExpiredConsumedBefore')
            ->with(self::isInstanceOf(\DateTimeImmutable::class))
            ->willReturn(3);

        $users = $this->createMock(AccessRepositoryInterface::class);
        $users->expects(self::exactly(2))->method('save')->with(self::isInstanceOf(AccessEntity::class), true);

        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::exactly(5))->method('record');

        $notifications = $this->createMock(AccessSecurityNotificationServiceInterface::class);
        $notifications->expects(self::once())->method('sendEmailVerificationCode');
        $notifications->expects(self::once())->method('sendPasswordRecoveryCode');

        $phone = $this->createMock(AccessPhoneVerificationProviderInterface::class);
        $phone->expects(self::once())->method('sendVerificationMessage')->with(
            '+17135550101',
            self::stringStartsWith('Accessing phone verification code: '),
        );

        $service = $this->service($repository, $users, $events, $phone, $notifications);
        $user = new AccessEntity('verify-flow@example.test');

        $email = $service->issueEmailVerification($user);
        self::assertFalse($email->challenge->isCompleted());
        self::assertMatchesRegularExpression('/^\d{6}$/', $email->plainCode);
        self::assertTrue($service->completeEmailVerification($user, $email->plainCode));
        self::assertTrue($user->isEmailVerified());

        $phoneChallenge = $service->issuePhoneVerification($user, '+17135550101');
        self::assertTrue($service->completePhoneVerification($user, $phoneChallenge->plainCode));
        self::assertSame('+17135550101', $user->getPhoneNumber());
        self::assertTrue($user->isPhoneVerified());

        $recovery = $service->issuePasswordRecovery($user);
        self::assertTrue($service->consumePasswordRecovery($user, $recovery->plainCode));
        self::assertSame(3, $service->cleanupExpiredChallenges());
    }

    public function testMissingAndWrongChallengesFailAndAttemptLimitTerminalizes(): void
    {
        $user = new AccessEntity('wrong-code@example.test');
        $repository = $this->createMock(AccessVerificationChallengeRepositoryInterface::class);
        $repository->expects(self::once())
            ->method('findLatestActiveForUser')
            ->willReturn(null);

        $service = $this->service(
            $repository,
            $this->createMock(AccessRepositoryInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
            $this->createMock(AccessPhoneVerificationProviderInterface::class),
            $this->createMock(AccessSecurityNotificationServiceInterface::class),
        );
        self::assertFalse($service->completeEmailVerification($user, '000000'));

        $challenge = new AccessVerificationChallengeEntity(
            $user,
            AccessVerificationChallengeType::PasswordRecovery,
            $user->getEmailAddress(),
            hash_hmac('sha256', '123456', 'test-secret'),
            new \DateTimeImmutable('+15 minutes'),
        );
        for ($i = 0; $i < 4; ++$i) {
            $challenge->registerAttempt();
        }

        $limitRepository = $this->createMock(AccessVerificationChallengeRepositoryInterface::class);
        $limitRepository->method('findLatestActiveForUser')->willReturn($challenge);
        $limitRepository->expects(self::once())->method('save')->with($challenge, true);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::VerificationAttemptLimitReached,
            AccessSecurityEventSeverity::Warning,
            $user,
            null,
            ['challengeType' => AccessVerificationChallengeType::PasswordRecovery->value],
        );

        $limitService = $this->service(
            $limitRepository,
            $this->createMock(AccessRepositoryInterface::class),
            $events,
            $this->createMock(AccessPhoneVerificationProviderInterface::class),
            $this->createMock(AccessSecurityNotificationServiceInterface::class),
        );
        self::assertFalse($limitService->consumePasswordRecovery($user, 'wrong'));
        self::assertTrue($challenge->isCompleted());
        self::assertSame(5, $challenge->getAttemptCount());
    }

    public function testWrongCodeBelowAttemptLimitAndMissingPhoneChallengeRemainNonTerminal(): void
    {
        $user = new AccessEntity('verification-branches@example.test');
        $challenge = new AccessVerificationChallengeEntity(
            $user,
            AccessVerificationChallengeType::PasswordRecovery,
            $user->getEmailAddress(),
            hash_hmac('sha256', '123456', 'test-secret'),
            new \DateTimeImmutable('+15 minutes'),
        );
        $repository = $this->createMock(AccessVerificationChallengeRepositoryInterface::class);
        $repository->method('findLatestActiveForUser')->willReturnOnConsecutiveCalls($challenge, null);
        $repository->expects(self::once())->method('save')->with($challenge, true);
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::never())->method('record');
        $service = $this->service(
            $repository,
            $this->createMock(AccessRepositoryInterface::class),
            $events,
            $this->createMock(AccessPhoneVerificationProviderInterface::class),
            $this->createMock(AccessSecurityNotificationServiceInterface::class),
        );

        self::assertFalse($service->consumePasswordRecovery($user, 'wrong'));
        self::assertFalse($challenge->isCompleted());
        self::assertFalse($service->completePhoneVerification($user, '000000'));
    }

    public function testAcceptedResendIssuesEmailVerification(): void
    {
        $user = new AccessEntity('resend-accepted@example.test');
        $repository = $this->createMock(AccessVerificationChallengeRepositoryInterface::class);
        $repository->expects(self::once())->method('save');
        $notifications = $this->createMock(AccessSecurityNotificationServiceInterface::class);
        $notifications->expects(self::once())->method('sendEmailVerificationCode');
        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::EmailVerificationRequested,
            AccessSecurityEventSeverity::Info,
            $user,
            null,
            ['channel' => 'email', 'purpose' => 'verification'],
        );
        $service = $this->service(
            $repository,
            $this->createMock(AccessRepositoryInterface::class),
            $events,
            $this->createMock(AccessPhoneVerificationProviderInterface::class),
            $notifications,
        );

        self::assertNotNull($service->resendEmailVerification($user));
    }

    public function testResendRateLimitReturnsNullAndRecordsSecurityEvent(): void
    {
        $user = new AccessEntity('resend-limit@example.test');
        $storage = new InMemoryStorage();
        $factory = new RateLimiterFactory([
            'id' => 'verification_resend_coverage',
            'policy' => 'fixed_window',
            'limit' => 1,
            'interval' => '1 hour',
        ], $storage);
        $factory->create($user->getEmailAddress().'|unknown')->consume();

        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::RateLimitExceeded,
            AccessSecurityEventSeverity::Warning,
            $user,
            null,
            ['flow' => 'verification_resend'],
        );

        $service = new AccessVerificationChallengeService(
            $this->createMock(AccessVerificationChallengeRepositoryInterface::class),
            $this->createMock(AccessRepositoryInterface::class),
            $events,
            $this->createMock(AccessPhoneVerificationProviderInterface::class),
            $this->createMock(AccessSecurityNotificationServiceInterface::class),
            $factory,
            'test-secret',
            15,
            30,
        );

        self::assertNull($service->resendEmailVerification($user));
    }

    public function testResendRateLimitUsesPersistedUserIdAndClientIp(): void
    {
        $user = new AccessEntity('persisted-resend@example.test');
        $id = new \ReflectionProperty(AccessEntity::class, 'id');
        $id->setValue($user, 42);

        $storage = new InMemoryStorage();
        $factory = new RateLimiterFactory([
            'id' => 'verification_resend_persisted_coverage',
            'policy' => 'fixed_window',
            'limit' => 1,
            'interval' => '1 hour',
        ], $storage);
        $factory->create('42|203.0.113.5')->consume();

        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())->method('record')->with(
            AccessSecurityEventType::RateLimitExceeded,
            AccessSecurityEventSeverity::Warning,
            $user,
            self::isInstanceOf(Request::class),
            ['flow' => 'verification_resend'],
        );
        $service = new AccessVerificationChallengeService(
            $this->createMock(AccessVerificationChallengeRepositoryInterface::class),
            $this->createMock(AccessRepositoryInterface::class),
            $events,
            $this->createMock(AccessPhoneVerificationProviderInterface::class),
            $this->createMock(AccessSecurityNotificationServiceInterface::class),
            $factory,
            'test-secret',
            15,
            30,
        );
        $request = Request::create('/access/verify/resend', 'POST', server: ['REMOTE_ADDR' => '203.0.113.5']);

        self::assertNull($service->resendEmailVerification($user, $request));
    }

    private function service(
        AccessVerificationChallengeRepositoryInterface $repository,
        AccessRepositoryInterface $users,
        AccessSecurityEventServiceInterface $events,
        AccessPhoneVerificationProviderInterface $phone,
        AccessSecurityNotificationServiceInterface $notifications,
    ): AccessVerificationChallengeService {
        return new AccessVerificationChallengeService(
            $repository,
            $users,
            $events,
            $phone,
            $notifications,
            new RateLimiterFactory([
                'id' => 'verification_coverage',
                'policy' => 'fixed_window',
                'limit' => 10,
                'interval' => '1 minute',
            ], new InMemoryStorage()),
            'test-secret',
            15,
            30,
        );
    }
}
