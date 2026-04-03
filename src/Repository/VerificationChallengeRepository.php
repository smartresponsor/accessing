<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\VerificationChallenge;
use App\RepositoryInterface\VerificationChallengeRepositoryInterface;
use App\ValueObject\VerificationChallengeType;
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
        return $this->createQueryBuilder('challenge')
            ->andWhere('challenge.account = :account')
            ->andWhere('challenge.challengeType = :challengeType')
            ->andWhere('challenge.consumedAt IS NULL')
            ->andWhere('challenge.expiresAt > :now')
            ->setParameter('account', $account)
            ->setParameter('challengeType', $challengeType)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('challenge.requestedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiredActiveChallenges(\DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('challenge')
            ->andWhere('challenge.consumedAt IS NULL')
            ->andWhere('challenge.expiresAt <= :before')
            ->setParameter('before', $before)
            ->orderBy('challenge.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function cleanupExpiredConsumedBefore(\DateTimeImmutable $before): int
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(VerificationChallenge::class, 'challenge')
            ->andWhere('(challenge.consumedAt IS NOT NULL AND challenge.consumedAt <= :before) OR challenge.expiresAt <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }
}
