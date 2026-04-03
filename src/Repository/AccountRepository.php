<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\RepositoryInterface\AccountRepositoryInterface;
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

    public function findOneByEmailAddress(string $emailAddress): ?Account
    {
        return $this->findOneBy(['emailAddress' => $emailAddress]);
    }

    public function findRecentAccounts(int $limit = 20): array
    {
        return $this->createQueryBuilder('account')
            ->orderBy('account.registeredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
