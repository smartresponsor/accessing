<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use PHPUnit\Framework\TestCase;

final class AccessPasskeyChallengeEntityTest extends TestCase
{
    public function testRegistrationChallengeRequiresUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AccessPasskeyChallengeEntity(
            'challenge',
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test',
            new \DateTimeImmutable('2026-07-11T12:00:00+00:00'),
            new \DateTimeImmutable('2026-07-11T12:05:00+00:00'),
        );
    }

    public function testChallengeIsBoundToPurposeRelyingPartyAndOrigin(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-11T12:00:00+00:00');
        $challenge = new AccessPasskeyChallengeEntity(
            'challenge',
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test/',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new AccessEntity('passkey@example.test', 'Passkey User'),
        );

        self::assertTrue($challenge->isUsable(
            'challenge',
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test',
            $createdAt->modify('+1 minute'),
        ));
        self::assertFalse($challenge->isUsable(
            'challenge',
            AccessPasskeyCeremonyPurpose::Authentication,
            'example.test',
            'https://example.test',
            $createdAt->modify('+1 minute'),
        ));
        self::assertFalse($challenge->isUsable(
            'challenge',
            AccessPasskeyCeremonyPurpose::Registration,
            'other.test',
            'https://example.test',
            $createdAt->modify('+1 minute'),
        ));
    }

    public function testConsumedChallengeCannotBeReplayed(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-11T12:00:00+00:00');
        $challenge = new AccessPasskeyChallengeEntity(
            'challenge',
            AccessPasskeyCeremonyPurpose::Authentication,
            'example.test',
            'https://example.test',
            $createdAt,
            $createdAt->modify('+5 minutes'),
        );
        $challenge->consume($createdAt->modify('+1 minute'));

        self::assertFalse($challenge->isUsable(
            'challenge',
            AccessPasskeyCeremonyPurpose::Authentication,
            'example.test',
            'https://example.test',
            $createdAt->modify('+2 minutes'),
        ));

        $this->expectException(\DomainException::class);
        $challenge->consume($createdAt->modify('+2 minutes'));
    }

    public function testExpiredChallengeCannotBeConsumed(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-11T12:00:00+00:00');
        $challenge = new AccessPasskeyChallengeEntity(
            'challenge',
            AccessPasskeyCeremonyPurpose::Authentication,
            'example.test',
            'https://example.test',
            $createdAt,
            $createdAt->modify('+5 minutes'),
        );

        $this->expectException(\DomainException::class);
        $challenge->consume($createdAt->modify('+5 minutes'));
    }
}
