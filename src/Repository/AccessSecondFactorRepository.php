<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use App\Accessing\RepositoryInterface\AccessSecondFactorRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessSecondFactorEntity>
 */
final class AccessSecondFactorRepository extends ServiceEntityRepository implements AccessSecondFactorRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessSecondFactorEntity::class);
    }

    public function save(AccessSecondFactorEntity $secondFactor, bool $flush = false): void
    {
        $this->getEntityManager()->persist($secondFactor);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findEnabledForUser(AccessEntity $user): ?AccessSecondFactorEntity
    {
        $secondFactor = $this->createQueryBuilder('secondFactor')
            ->andWhere('secondFactor.user = :user')
            ->andWhere('secondFactor.confirmedAt IS NOT NULL')
            ->andWhere('secondFactor.revokedAt IS NULL')
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $secondFactor instanceof AccessSecondFactorEntity ? $secondFactor : null;
    }
}
