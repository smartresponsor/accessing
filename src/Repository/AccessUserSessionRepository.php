<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\Entity\AccessUserSessionEntity;
use App\Accessing\RepositoryInterface\AccessUserSessionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessUserSessionEntity>
 */
final class AccessUserSessionRepository extends ServiceEntityRepository implements AccessUserSessionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessUserSessionEntity::class);
    }

    public function save(AccessUserSessionEntity $userSession, bool $flush = false): void
    {
        $this->getEntityManager()->persist($userSession);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneBySessionIdentifier(string $sessionIdentifier): ?AccessUserSessionEntity
    {
        $userSession = $this->findOneBy(['sessionIdentifier' => $sessionIdentifier]);

        return $userSession instanceof AccessUserSessionEntity ? $userSession : null;
    }

    public function findActiveForUser(AccessUserEntity $user): array
    {
        /** @var list<AccessUserSessionEntity> $results */
        $results = $this->createQueryBuilder('userSession')
            ->andWhere('userSession.user = :user')
            ->andWhere('userSession.revokedAt IS NULL')
            ->setParameter('user', $user)
            ->orderBy('userSession.lastSeenAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $results;
    }

    public function invalidateOtherActiveSessions(AccessUserEntity $user, string $keepSessionIdentifier): int
    {
        /** @var int $updatedCount */
        $updatedCount = $this->getEntityManager()->createQueryBuilder()
            ->update(AccessUserSessionEntity::class, 'userSession')
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

    public function cleanupInvalidatedBefore(\DateTimeImmutable $before): int
    {
        /** @var int $deletedCount */
        $deletedCount = $this->getEntityManager()->createQueryBuilder()
            ->delete(AccessUserSessionEntity::class, 'userSession')
            ->where('userSession.revokedAt IS NOT NULL')
            ->andWhere('userSession.revokedAt <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();

        return $deletedCount;
    }
}
