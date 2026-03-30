<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends ServiceEntityRepository<Account>
 */
final class AccountRepository extends ServiceEntityRepository
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
        return $this->createQueryBuilder('account')
            ->andWhere('LOWER(account.email) = :email')
            ->setParameter('email', mb_strtolower(trim($email)))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
