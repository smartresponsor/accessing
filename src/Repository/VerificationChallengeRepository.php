<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\Account;
use App\Accessing\Entity\VerificationChallenge;
use App\Accessing\RepositoryInterface\VerificationChallengeRepositoryInterface;
use App\Accessing\ValueObject\VerificationChallengeType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerificationChallenge>
 */
final class VerificationChallengeRepository extends ServiceEntityRepository implements VerificationChallengeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerificationChallenge::class);
    }

    public function save(VerificationChallenge $verificationChallenge, bool $flush = false): void
    {
        $this->getEntityManager()->persist($verificationChallenge);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findLatestActiveForAccount(Account $account, VerificationChallengeType $challengeType): ?VerificationChallenge
    {
        $challenge = $this->createQueryBuilder('challenge')
            ->andWhere('challenge.account = :account')
            ->andWhere('challenge.channelType = :channelType')
            ->andWhere('challenge.completed = false')
            ->andWhere('challenge.expiresAt > :now')
            ->setParameter('account', $account)
            ->setParameter('channelType', match ($challengeType) {
                VerificationChallengeType::EmailVerification => 'email',
                VerificationChallengeType::PhoneVerification => 'phone',
                VerificationChallengeType::PasswordRecovery => 'recovery',
            })
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('challenge.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $challenge instanceof VerificationChallenge ? $challenge : null;
    }

    /** @return list<VerificationChallenge> */
    public function findExpiredActiveChallenges(\DateTimeImmutable $before): array
    {
        /** @var list<VerificationChallenge> $results */
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
            ->delete(VerificationChallenge::class, 'challenge')
            ->andWhere('(challenge.completedAt IS NOT NULL AND challenge.completedAt <= :before) OR challenge.expiresAt <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();

        return $deletedCount;
    }
}
