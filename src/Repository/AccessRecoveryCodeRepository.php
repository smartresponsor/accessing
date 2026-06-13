<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\RepositoryInterface\AccessRecoveryCodeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessRecoveryCodeEntity>
 */
final class AccessRecoveryCodeRepository extends ServiceEntityRepository implements AccessRecoveryCodeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessRecoveryCodeEntity::class);
    }

    public function save(AccessRecoveryCodeEntity $recoveryCode, bool $flush = false): void
    {
        $this->getEntityManager()->persist($recoveryCode);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveForUser(AccessEntity $user): array
    {
        /** @var list<AccessRecoveryCodeEntity> $results */
        $results = $this->createQueryBuilder('recoveryCode')
            ->andWhere('recoveryCode.user = :user')
            ->andWhere('recoveryCode.consumedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('recoveryCode.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function cleanupConsumedBefore(\DateTimeImmutable $before): int
    {
        /** @var int $deletedCount */
        $deletedCount = $this->getEntityManager()->createQueryBuilder()
            ->delete(AccessRecoveryCodeEntity::class, 'recoveryCode')
            ->andWhere('recoveryCode.consumedAt IS NOT NULL')
            ->andWhere('recoveryCode.consumedAt <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();

        return $deletedCount;
    }
}
