<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\RepositoryInterface\AccessMobilePendingAuthRepositoryInterface;
use App\Accessing\Service\Mobile\AccessMobilePendingAuthService;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class AccessMobilePendingAuthServiceTest extends TestCase
{
    public function testIssuesResolvesAndConsumesOneTimeToken(): void
    {
        $now = new \DateTimeImmutable('2026-07-12T00:00:00+00:00');
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);
        $stored = null;

        $repository = $this->createMock(AccessMobilePendingAuthRepositoryInterface::class);
        $repository->method('save')->willReturnCallback(
            static function (AccessMobilePendingAuthEntity $pendingAuth) use (&$stored): void {
                $stored = $pendingAuth;
            },
        );
        $repository->method('findOneByTokenHash')->willReturnCallback(
            static function (string $tokenHash) use (&$stored): ?AccessMobilePendingAuthEntity {
                return $stored instanceof AccessMobilePendingAuthEntity ? $stored : null;
            },
        );

        $service = new AccessMobilePendingAuthService($repository, $clock, 600);
        $issued = $service->issue(
            new AccessEntity('pending-service@example.test', 'Pending Service'),
            AccessMobilePendingPurpose::EmailVerification,
            'Test Android',
        );

        self::assertSame(43, strlen($issued->token));
        self::assertEquals($now->modify('+10 minutes'), $issued->expiresAt);
        $resolved = $service->resolve($issued->token, AccessMobilePendingPurpose::EmailVerification);
        self::assertSame(AccessMobilePendingPurpose::EmailVerification, $resolved->getPurpose());
        self::assertSame('Test Android', $resolved->getDeviceName());

        $consumed = $service->consume($issued->token, AccessMobilePendingPurpose::EmailVerification);
        self::assertSame($resolved->getUser(), $consumed->getUser());

        $this->expectException(\DomainException::class);
        $service->resolve($issued->token, AccessMobilePendingPurpose::EmailVerification);
    }

    public function testRejectsPurposeMismatchWithoutConsumingToken(): void
    {
        $now = new \DateTimeImmutable('2026-07-12T00:00:00+00:00');
        $pending = new AccessMobilePendingAuthEntity(
            new AccessEntity('purpose@example.test', 'Purpose'),
            'purpose-token',
            AccessMobilePendingPurpose::SecondFactor,
            'iPhone',
            $now,
            $now->modify('+10 minutes'),
        );
        $clock = $this->createMock(ClockInterface::class);
        $clock->method('now')->willReturn($now);
        $repository = $this->createMock(AccessMobilePendingAuthRepositoryInterface::class);
        $repository->method('findOneByTokenHash')->willReturn($pending);
        $service = new AccessMobilePendingAuthService($repository, $clock);

        $this->expectException(\DomainException::class);
        $service->resolve('purpose-token', AccessMobilePendingPurpose::EmailVerification);
    }
}
