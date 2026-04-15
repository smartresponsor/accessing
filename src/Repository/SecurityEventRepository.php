<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\SecurityEvent;
use App\RepositoryInterface\SecurityEventRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SecurityEvent>
 */
final class SecurityEventRepository extends ServiceEntityRepository implements SecurityEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SecurityEvent::class);
    }

    public function save(SecurityEvent $securityEvent, bool $flush = false): void
    {
        $this->getEntityManager()->persist($securityEvent);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findRecentEvents(int $limit = 50): array
    {
        $query = $this->createQueryBuilder('securityEvent')
            ->orderBy('securityEvent.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        /** @var list<SecurityEvent> $results */
        $results = $query->getResult();

        return $results;
    }

    public function findRecentEventsForAccount(Account $account, int $limit = 50): array
    {
        $query = $this->createQueryBuilder('securityEvent')
            ->andWhere('securityEvent.account = :account')
            ->setParameter('account', $account)
            ->orderBy('securityEvent.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        /** @var list<SecurityEvent> $results */
        $results = $query->getResult();

        return $results;
    }
}
