<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\RepositoryInterface\AccessUserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessUserEntity>
 */
final class AccessUserRepository extends ServiceEntityRepository implements AccessUserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessUserEntity::class);
    }

    public function save(AccessUserEntity $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AccessUserEntity $user, bool $flush = false): void
    {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByEmail(string $email): ?AccessUserEntity
    {
        return $this->findOneByEmailAddress($email);
    }

    public function findById(int $id): ?AccessUserEntity
    {
        $user = $this->find($id);

        return $user instanceof AccessUserEntity ? $user : null;
    }

    public function findOneByEmailAddress(string $emailAddress): ?AccessUserEntity
    {
        $user = $this->createQueryBuilder('user')
            ->andWhere('LOWER(user.email) = :email')
            ->setParameter('email', mb_strtolower(trim($emailAddress)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $user instanceof AccessUserEntity ? $user : null;
    }

    /** @return list<AccessUserEntity> */
    public function findRecentUsers(int $limit = 50): array
    {
        /** @var list<AccessUserEntity> $users */
        $users = $this->createQueryBuilder('user')
            ->orderBy('user.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $users;
    }
}
