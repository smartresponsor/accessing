<?php

declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessMobileSessionEntity;
use App\Accessing\RepositoryInterface\AccessMobileSessionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AccessMobileSessionEntity> */
final class AccessMobileSessionRepository extends ServiceEntityRepository implements AccessMobileSessionRepositoryInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessMobileSessionEntity::class);
    }

    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessMobileSessionEntity $session, bool $flush = false): void
    {
        $this->getEntityManager()->persist($session);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Executes the find one by access token hash operation within the canonical Accessing component workflow.
     */
    public function findOneByAccessTokenHash(string $tokenHash): ?AccessMobileSessionEntity
    {
        $session = $this->findOneBy(['accessTokenHash' => $tokenHash]);

        return $session instanceof AccessMobileSessionEntity ? $session : null;
    }

    /**
     * Executes the find one by refresh token hash operation within the canonical Accessing component workflow.
     */
    public function findOneByRefreshTokenHash(string $tokenHash): ?AccessMobileSessionEntity
    {
        $session = $this->findOneBy(['refreshTokenHash' => $tokenHash]);

        return $session instanceof AccessMobileSessionEntity ? $session : null;
    }

    /**
     * Executes the find one by previous refresh token hash operation within the canonical Accessing component workflow.
     */
    public function findOneByPreviousRefreshTokenHash(string $tokenHash): ?AccessMobileSessionEntity
    {
        $session = $this->findOneBy(['previousRefreshTokenHash' => $tokenHash]);

        return $session instanceof AccessMobileSessionEntity ? $session : null;
    }
}
