<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use PHPUnit\Framework\TestCase;

final class AccessMobilePendingAuthEntityTest extends TestCase
{
    public function testBindsPurposeAndPreventsReplay(): void
    {
        $now = new \DateTimeImmutable('2026-07-12T00:00:00+00:00');
        $pending = new AccessMobilePendingAuthEntity(
            new AccessEntity('pending@example.test', 'Pending'),
            'plain-pending-token',
            AccessMobilePendingPurpose::SecondFactor,
            'Test iPhone',
            $now,
            $now->modify('+10 minutes'),
        );

        self::assertTrue($pending->hasToken('plain-pending-token'));
        self::assertTrue($pending->isUsable(AccessMobilePendingPurpose::SecondFactor, $now));
        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now));

        $pending->consume($now->modify('+1 minute'));

        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::SecondFactor, $now->modify('+2 minutes')));
        $this->expectException(\DomainException::class);
        $pending->consume($now->modify('+2 minutes'));
    }

    public function testRejectsExpiredToken(): void
    {
        $now = new \DateTimeImmutable('2026-07-12T00:00:00+00:00');
        $pending = new AccessMobilePendingAuthEntity(
            new AccessEntity('expired@example.test', 'Expired'),
            'expired-token',
            AccessMobilePendingPurpose::EmailVerification,
            'Android',
            $now,
            $now->modify('+10 minutes'),
        );

        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now->modify('+11 minutes')));
    }
}
