<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSessionEntity;
use App\Accessing\RepositoryInterface\AccessSessionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessSessionEntity>
 */
final class AccessSessionRepository extends ServiceEntityRepository implements AccessSessionRepositoryInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessSessionEntity::class);
    }

    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessSessionEntity $userSession, bool $flush = false): void
    {
        $this->getEntityManager()->persist($userSession);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Executes the find one by session identifier operation within the canonical Accessing component workflow.
     */
    public function findOneBySessionIdentifier(string $sessionIdentifier): ?AccessSessionEntity
    {
        $userSession = $this->findOneBy(['sessionIdentifier' => $sessionIdentifier]);

        return $userSession instanceof AccessSessionEntity ? $userSession : null;
    }

    /**
     * Executes the find active for user operation within the canonical Accessing component workflow.
     */
    public function findActiveForUser(AccessEntity $user): array
    {
        /** @var list<AccessSessionEntity> $results */
        $results = $this->createQueryBuilder('userSession')
            ->andWhere('userSession.user = :user')
            ->andWhere('userSession.revokedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('userSession.lastSeenAt', \SortDirection::Descending)
            ->getQuery()
            ->getResult();

        return $results;
    }

    /**
     * Executes the invalidate other active sessions operation within the canonical Accessing component workflow.
     */
    public function invalidateOtherActiveSessions(AccessEntity $user, string $keepSessionIdentifier): int
    {
        /** @var int $updatedCount */
        $updatedCount = $this->getEntityManager()->createQueryBuilder()
            ->update(AccessSessionEntity::class, 'userSession')
            ->set('userSession.revokedAt', ':now')
            ->where('userSession.user = :user')
            ->andWhere('userSession.revokedAt IS NULL')
            ->andWhere('userSession.sessionIdentifier != :keepSessionIdentifier')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->setParameter('keepSessionIdentifier', $keepSessionIdentifier)
            ->getQuery()
            ->execute();

        return $updatedCount;
    }

    /**
     * Executes the cleanup invalidated before operation within the canonical Accessing component workflow.
     */
    public function cleanupInvalidatedBefore(\DateTimeImmutable $before): int
    {
        /** @var int $deletedCount */
        $deletedCount = $this->getEntityManager()->createQueryBuilder()
            ->delete(AccessSessionEntity::class, 'userSession')
            ->where('userSession.revokedAt IS NOT NULL')
            ->andWhere('userSession.revokedAt <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();

        return $deletedCount;
    }
}
