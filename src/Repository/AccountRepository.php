<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessAccountEntity>
 */
final class AccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessAccountEntity::class);
    }

    public function save(AccessAccountEntity $account, bool $flush = false): void
    {
        $this->getEntityManager()->persist($account);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AccessAccountEntity $account, bool $flush = false): void
    {
        $this->getEntityManager()->remove($account);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByEmail(string $email): ?AccessAccountEntity
    {
        return $this->findOneByEmailAddress($email);
    }

    public function findById(int $id): ?AccessAccountEntity
    {
        $account = $this->find($id);

        return $account instanceof AccessAccountEntity ? $account : null;
    }

    public function findOneByEmailAddress(string $emailAddress): ?AccessAccountEntity
    {
        $account = $this->createQueryBuilder('account')
            ->andWhere('LOWER(account.email) = :email')
            ->setParameter('email', mb_strtolower(trim($emailAddress)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $account instanceof AccessAccountEntity ? $account : null;
    }

    /** @return list<AccessAccountEntity> */
    public function findRecentAccounts(int $limit = 50): array
    {
        /** @var list<AccessAccountEntity> $accounts */
        $accounts = $this->createQueryBuilder('account')
            ->orderBy('account.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $accounts;
    }
}
