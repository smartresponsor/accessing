<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\Account;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Account>
 */
final class AccountRepository extends ServiceEntityRepository implements AccountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function save(Account $account, bool $flush = false): void
    {
        $this->getEntityManager()->persist($account);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Account $account, bool $flush = false): void
    {
        $this->getEntityManager()->remove($account);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByEmail(string $email): ?Account
    {
        return $this->findOneByEmailAddress($email);
    }

    public function findById(int $id): ?Account
    {
        $account = $this->find($id);

        return $account instanceof Account ? $account : null;
    }

    public function findOneByEmailAddress(string $emailAddress): ?Account
    {
        $account = $this->createQueryBuilder('account')
            ->andWhere('LOWER(account.email) = :email')
            ->setParameter('email', mb_strtolower(trim($emailAddress)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $account instanceof Account ? $account : null;
    }

    /** @return list<Account> */
    public function findRecentAccounts(int $limit = 50): array
    {
        /** @var list<Account> $accounts */
        $accounts = $this->createQueryBuilder('account')
            ->orderBy('account.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $accounts;
    }
}
