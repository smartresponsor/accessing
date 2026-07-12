<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyChallengeRepositoryInterface;
use App\Accessing\Service\Passkey\AccessPasskeyChallengeService;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

final class AccessPasskeyChallengeServiceTest extends TestCase
{
    public function testIssuesBoundRegistrationChallenge(): void
    {
        $now = new \DateTimeImmutable('2026-07-11T12:00:00+00:00');
        $repository = $this->createMock(AccessPasskeyChallengeRepositoryInterface::class);
        $repository->expects(self::once())->method('save')->with(self::isInstanceOf(AccessPasskeyChallengeEntity::class), true);

        $issued = (new AccessPasskeyChallengeService($repository, self::clock($now), 300))->issue(
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test',
            new AccessEntity('passkey-challenge@example.test', 'Passkey Challenge'),
        );

        self::assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $issued['challenge']);
        self::assertSame(AccessPasskeyCeremonyPurpose::Registration, $issued['state']->getPurpose());
        self::assertEquals($now->modify('+300 seconds'), $issued['state']->getExpiresAt());
    }

    public function testConsumesMatchingChallenge(): void
    {
        $now = new \DateTimeImmutable('2026-07-11T12:00:00+00:00');
        $entity = new AccessPasskeyChallengeEntity(
            'challenge-value',
            AccessPasskeyCeremonyPurpose::Authentication,
            'example.test',
            'https://example.test',
            $now->modify('-1 minute'),
            $now->modify('+1 minute'),
        );
        $repository = $this->createMock(AccessPasskeyChallengeRepositoryInterface::class);
        $repository->method('findOneByChallengeHash')->with(hash('sha256', 'challenge-value'))->willReturn($entity);
        $repository->expects(self::once())->method('save')->with($entity, true);

        $consumed = (new AccessPasskeyChallengeService($repository, self::clock($now), 300))->consume(
            'challenge-value',
            AccessPasskeyCeremonyPurpose::Authentication,
            'example.test',
            'https://example.test/',
        );

        self::assertSame($entity, $consumed);
        self::assertEquals($now, $entity->getConsumedAt());
    }

    public function testRejectsOriginMismatch(): void
    {
        $now = new \DateTimeImmutable('2026-07-11T12:00:00+00:00');
        $entity = new AccessPasskeyChallengeEntity('challenge', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://example.test', $now, $now->modify('+5 minutes'));
        $repository = $this->createMock(AccessPasskeyChallengeRepositoryInterface::class);
        $repository->method('findOneByChallengeHash')->willReturn($entity);
        $repository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);
        (new AccessPasskeyChallengeService($repository, self::clock($now), 300))->consume('challenge', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://evil.test');
    }

    public function testRejectsReplay(): void
    {
        $createdAt = new \DateTimeImmutable('2026-07-11T12:00:00+00:00');
        $entity = new AccessPasskeyChallengeEntity('challenge', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://example.test', $createdAt, $createdAt->modify('+5 minutes'));
        $entity->consume($createdAt->modify('+1 minute'));
        $repository = $this->createMock(AccessPasskeyChallengeRepositoryInterface::class);
        $repository->method('findOneByChallengeHash')->willReturn($entity);

        $this->expectException(\DomainException::class);
        (new AccessPasskeyChallengeService($repository, self::clock($createdAt->modify('+2 minutes')), 300))->consume('challenge', AccessPasskeyCeremonyPurpose::Authentication, 'example.test', 'https://example.test');
    }

    private static function clock(\DateTimeImmutable $now): ClockInterface
    {
        return new readonly class($now) implements ClockInterface {
            public function __construct(private \DateTimeImmutable $now)
            {
            }

            public function now(): \DateTimeImmutable
            {
                return $this->now;
            }
        };
    }
}
