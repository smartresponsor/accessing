<?php

declare(strict_types=1);

namespace App\Service\AccountSession;

use App\Entity\Account;
use App\ServiceInterface\AccountSession\AccessingAccountSessionViewProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

final class AccessingDoctrineAccountSessionViewProviderService implements AccessingAccountSessionViewProviderInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function listRecentForAccount(Account $account, int $limit = 50): array
    {
        return $this->entityManager->createQuery(
            'SELECT s FROM App\\Entity\\AccountSession s WHERE s.account = :account ORDER BY s.lastSeenAt DESC'
        )
            ->setParameter('account', $account)
            ->setMaxResults($limit)
            ->getResult();
    }
}
