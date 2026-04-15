<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\AccountSession;
use App\RepositoryInterface\AccountSessionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccountSession>
 */
final class AccountSessionRepository extends ServiceEntityRepository implements AccountSessionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccountSession::class);
    }

    public function save(AccountSession $accountSession, bool $flush = false): void
    {
        $this->getEntityManager()->persist($accountSession);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneBySessionIdentifier(string $sessionIdentifier): ?AccountSession
    {
        $accountSession = $this->findOneBy(['sessionIdentifier' => $sessionIdentifier]);

        return $accountSession instanceof AccountSession ? $accountSession : null;
    }

    public function findActiveForAccount(Account $account): array
    {
        /** @var list<AccountSession> $results */
        $results = $this->createQueryBuilder('accountSession')
            ->andWhere('accountSession.account = :account')
            ->andWhere('accountSession.revokedAt IS NULL')
            ->setParameter('account', $account)
            ->orderBy('accountSession.lastSeenAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function invalidateOtherActiveSessions(Account $account, string $keepSessionIdentifier): int
    {
        /** @var int $updatedCount */
        $updatedCount = $this->getEntityManager()->createQueryBuilder()
            ->update(AccountSession::class, 'accountSession')
            ->set('accountSession.revokedAt', ':now')
            ->where('accountSession.account = :account')
            ->andWhere('accountSession.revokedAt IS NULL')
            ->andWhere('accountSession.sessionIdentifier != :keepSessionIdentifier')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('account', $account)
            ->setParameter('keepSessionIdentifier', $keepSessionIdentifier)
            ->getQuery()
            ->execute();

        return $updatedCount;
    }

    public function cleanupInvalidatedBefore(\DateTimeImmutable $before): int
    {
        /** @var int $deletedCount */
        $deletedCount = $this->getEntityManager()->createQueryBuilder()
            ->delete(AccountSession::class, 'accountSession')
            ->where('accountSession.revokedAt IS NOT NULL')
            ->andWhere('accountSession.revokedAt <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();

        return $deletedCount;
    }
}
