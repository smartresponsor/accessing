<?php

declare(strict_types=1);

namespace App\Service\SecurityEvent;

use App\Entity\Account;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventViewProviderInterface;
use Doctrine\ORM\EntityManagerInterface;

final class AccessingDoctrineSecurityEventViewProviderService implements AccessingSecurityEventViewProviderInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    public function listRecentForAccount(Account $account, int $limit = 50): array
    {
        return $this->entityManager->createQuery(
            'SELECT e FROM App\\Entity\\SecurityEvent e WHERE e.account = :account ORDER BY e.occurredAt DESC'
        )
            ->setParameter('account', $account)
            ->setMaxResults($limit)
            ->getResult();
    }
}
