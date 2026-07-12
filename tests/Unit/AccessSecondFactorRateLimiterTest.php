<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use App\Accessing\Service\Clock\AccessSystemClock;
use App\Accessing\Service\SecondFactor\AccessSecondFactorService;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use PHPUnit\Framework\TestCase;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

final class AccessSecondFactorRateLimiterTest extends TestCase
{
    public function testEachVerificationConsumesOnlyOneLimiterToken(): void
    {
        $clock = new AccessSystemClock();
        $secret = TOTP::create(clock: $clock)->getSecret();
        $user = new AccessEntity('single-consume@example.test', 'Single Consume');
        $secondFactor = new AccessSecondFactorEntity($user, $secret, $user->getEmailAddress());
        $secondFactor->confirm();
        $user->setSecondFactor($secondFactor);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $limiter = new RateLimiterFactory([
            'id' => 'accessing_second_factor_single_consume_test',
            'policy' => 'sliding_window',
            'limit' => 5,
            'interval' => '15 minutes',
        ], new InMemoryStorage());

        $service = new AccessSecondFactorService(
            $entityManager,
            $this->createMock(AccessSecurityEventServiceInterface::class),
            $limiter,
            $clock,
            'test-secret',
        );

        for ($attempt = 0; $attempt < 4; ++$attempt) {
            self::assertFalse($service->verifyChallenge($user, '000000'));
        }

        $validCode = TOTP::create($secret, clock: $clock)->now();
        self::assertTrue($service->verifyChallenge($user, $validCode));
    }

    public function testValidCodeIsRejectedAfterAttemptBudgetIsExhausted(): void
    {
        $clock = new AccessSystemClock();
        $secret = TOTP::create(clock: $clock)->getSecret();
        $user = new AccessEntity('second-factor@example.test', 'Second Factor');
        $secondFactor = new AccessSecondFactorEntity($user, $secret, $user->getEmailAddress());
        $secondFactor->confirm();
        $user->setSecondFactor($secondFactor);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $limiter = new RateLimiterFactory([
            'id' => 'accessing_second_factor_test',
            'policy' => 'sliding_window',
            'limit' => 5,
            'interval' => '15 minutes',
        ], new InMemoryStorage());

        $service = new AccessSecondFactorService(
            $entityManager,
            $this->createMock(AccessSecurityEventServiceInterface::class),
            $limiter,
            $clock,
            'test-secret',
        );

        for ($attempt = 0; $attempt < 5; ++$attempt) {
            self::assertFalse($service->verifyChallenge($user, '000000'));
        }

        $validCode = TOTP::create($secret, clock: $clock)->now();
        self::assertFalse($service->verifyChallenge($user, $validCode));
    }
}
