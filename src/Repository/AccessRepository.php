<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessEntity>
 */
final class AccessRepository extends ServiceEntityRepository implements AccessRepositoryInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessEntity::class);
    }

    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessEntity $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Executes the remove operation within the canonical Accessing component workflow.
     */
    public function remove(AccessEntity $user, bool $flush = false): void
    {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Executes the find one by email operation within the canonical Accessing component workflow.
     */
    public function findOneByEmail(string $email): ?AccessEntity
    {
        return $this->findOneByEmailAddress($email);
    }

    /**
     * Executes the find by id operation within the canonical Accessing component workflow.
     */
    public function findById(int $id): ?AccessEntity
    {
        $user = $this->find($id);

        return $user instanceof AccessEntity ? $user : null;
    }

    /**
     * Executes the find one by email address operation within the canonical Accessing component workflow.
     */
    public function findOneByEmailAddress(string $emailAddress): ?AccessEntity
    {
        $user = $this->createQueryBuilder('user')
            ->leftJoin('user.credential', 'credential')
            ->addSelect('credential')
            ->leftJoin('user.secondFactor', 'secondFactor')
            ->addSelect('secondFactor')
            ->andWhere('user.email = :email')
            ->setParameter('email', mb_strtolower(trim($emailAddress)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $user instanceof AccessEntity ? $user : null;
    }

    /** @return list<AccessEntity> */
    public function findRecentUsers(int $limit = 50): array
    {
        /** @var list<AccessEntity> $users */
        $users = $this->createQueryBuilder('user')
            ->orderBy('user.createdAt', \SortDirection::Descending)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $users;
    }
}
