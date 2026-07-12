<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\Exception\AccessNotificationDeliveryException;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessVerificationChallengeRepositoryInterface;
use App\Accessing\Service\Verification\AccessVerificationChallengeService;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\SecurityNotification\AccessSecurityNotificationServiceInterface;
use App\Accessing\ServiceInterface\Vendor\AccessPhoneVerificationProviderServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class AccessVerificationDeliveryFailureTest extends TestCase
{
    public function testEmailDeliveryFailureTerminalizesChallengeAndThrowsStableException(): void
    {
        $saved = [];
        $repository = $this->createMock(AccessVerificationChallengeRepositoryInterface::class);
        $repository->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(static function (AccessVerificationChallengeEntity $challenge) use (&$saved): void {
                $saved[] = $challenge->isCompleted();
            });

        $events = $this->createMock(AccessSecurityEventServiceInterface::class);
        $events->expects(self::once())
            ->method('record')
            ->with(
                AccessSecurityEventType::NotificationDeliveryFailed,
                self::anything(),
                self::isInstanceOf(AccessEntity::class),
                null,
                ['channel' => 'email', 'purpose' => 'verification'],
            );

        $notification = $this->createMock(AccessSecurityNotificationServiceInterface::class);
        $notification->method('sendEmailVerificationCode')->willThrowException(new \RuntimeException('provider secret failure'));

        $service = new AccessVerificationChallengeService(
            $repository,
            $this->createMock(AccessRepositoryInterface::class),
            $events,
            $this->createMock(AccessPhoneVerificationProviderServiceInterface::class),
            $notification,
            new RateLimiterFactory(['id' => 'test', 'policy' => 'fixed_window', 'limit' => 10, 'interval' => '1 minute'], new InMemoryStorage()),
            'test-secret',
            15,
            30,
        );

        try {
            $service->issueEmailVerification(new AccessEntity('delivery@example.test'));
            self::fail('Expected delivery exception.');
        } catch (AccessNotificationDeliveryException $exception) {
            self::assertSame('Access notification delivery is temporarily unavailable.', $exception->getMessage());
            self::assertStringNotContainsString('provider secret failure', $exception->getMessage());
        }

        self::assertSame([false, true], $saved);
    }

    public function testPhoneDeliveryFailureAlsoTerminalizesChallenge(): void
    {
        $saved = [];
        $repository = $this->createMock(AccessVerificationChallengeRepositoryInterface::class);
        $repository->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(static function (AccessVerificationChallengeEntity $challenge) use (&$saved): void {
                $saved[] = $challenge->isCompleted();
            });

        $phone = $this->createMock(AccessPhoneVerificationProviderServiceInterface::class);
        $phone->method('sendVerificationMessage')->willThrowException(new \RuntimeException('sms provider failure'));

        $service = new AccessVerificationChallengeService(
            $repository,
            $this->createMock(AccessRepositoryInterface::class),
            $this->createMock(AccessSecurityEventServiceInterface::class),
            $phone,
            $this->createMock(AccessSecurityNotificationServiceInterface::class),
            new RateLimiterFactory(['id' => 'test', 'policy' => 'fixed_window', 'limit' => 10, 'interval' => '1 minute'], new InMemoryStorage()),
            'test-secret',
            15,
            30,
        );

        $this->expectException(AccessNotificationDeliveryException::class);

        try {
            $service->issuePhoneVerification(new AccessEntity('phone@example.test'), '+15555550123');
        } finally {
            self::assertSame([false, true], $saved);
        }
    }
}
