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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessEntity::class);
    }

    public function save(AccessEntity $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AccessEntity $user, bool $flush = false): void
    {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByEmail(string $email): ?AccessEntity
    {
        return $this->findOneByEmailAddress($email);
    }

    public function findById(int $id): ?AccessEntity
    {
        $user = $this->find($id);

        return $user instanceof AccessEntity ? $user : null;
    }

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
            ->orderBy('user.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $users;
    }
}
