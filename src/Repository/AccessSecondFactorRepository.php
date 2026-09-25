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
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessSecondFactorEntity::class);
    }

    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessSecondFactorEntity $secondFactor, bool $flush = false): void
    {
        $this->getEntityManager()->persist($secondFactor);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Executes the find enabled for user operation within the canonical Accessing component workflow.
     */
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
