<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecurityEventEntity;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessSecurityEventEntity>
 */
final class AccessSecurityEventRepository extends ServiceEntityRepository implements AccessSecurityEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessSecurityEventEntity::class);
    }

    public function save(AccessSecurityEventEntity $securityEvent, bool $flush = false): void
    {
        $user = $securityEvent->getUser();
        if ($user instanceof AccessEntity) {
            $entityManager = $this->getEntityManager();

            if (null !== $user->getId()) {
                $securityEvent->setUser($entityManager->getReference(AccessEntity::class, $user->getId()));
            } elseif (!$entityManager->contains($user)) {
                $entityManager->persist($user);
            }
        }

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

        /** @var list<AccessSecurityEventEntity> $results */
        $results = $query->getResult();

        return $results;
    }

    public function findRecentEventsForUser(AccessEntity $user, int $limit = 50): array
    {
        $query = $this->createQueryBuilder('securityEvent')
            ->andWhere('securityEvent.user = :user')
            ->setParameter('user', $user)
            ->orderBy('securityEvent.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery();

        /** @var list<AccessSecurityEventEntity> $results */
        $results = $query->getResult();

        return $results;
    }
}
