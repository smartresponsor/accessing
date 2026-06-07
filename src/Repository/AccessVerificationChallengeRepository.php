<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessVerificationChallengeEntity;
use App\Accessing\RepositoryInterface\AccessVerificationChallengeRepositoryInterface;
use App\Accessing\ValueObject\AccessVerificationChallengeType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessVerificationChallengeEntity>
 */
final class AccessVerificationChallengeRepository extends ServiceEntityRepository implements AccessVerificationChallengeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessVerificationChallengeEntity::class);
    }

    public function save(AccessVerificationChallengeEntity $verificationChallenge, bool $flush = false): void
    {
        $this->getEntityManager()->persist($verificationChallenge);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLatestActiveForUser(AccessEntity $user, AccessVerificationChallengeType $challengeType): ?AccessVerificationChallengeEntity
    {
        $challenge = $this->createQueryBuilder('challenge')
            ->andWhere('challenge.user = :user')
            ->andWhere('challenge.channelType = :channelType')
            ->andWhere('challenge.completed = false')
            ->andWhere('challenge.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('channelType', match ($challengeType) {
                AccessVerificationChallengeType::EmailVerification => 'email',
                AccessVerificationChallengeType::PhoneVerification => 'phone',
                AccessVerificationChallengeType::PasswordRecovery => 'recovery',
            })
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('challenge.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $challenge instanceof AccessVerificationChallengeEntity ? $challenge : null;
    }

    /** @return list<AccessVerificationChallengeEntity> */
    public function findExpiredActiveChallenges(\DateTimeImmutable $before): array
    {
        /** @var list<AccessVerificationChallengeEntity> $results */
        $results = $this->createQueryBuilder('challenge')
            ->andWhere('challenge.completed = false')
            ->andWhere('challenge.expiresAt <= :before')
            ->setParameter('before', $before)
            ->orderBy('challenge.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function cleanupExpiredConsumedBefore(\DateTimeImmutable $before): int
    {
        /** @var int $deletedCount */
        $deletedCount = $this->getEntityManager()->createQueryBuilder()
            ->delete(AccessVerificationChallengeEntity::class, 'challenge')
            ->andWhere('(challenge.completedAt IS NOT NULL AND challenge.completedAt <= :before) OR challenge.expiresAt <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();

        return $deletedCount;
    }
}
