<?php

declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\RepositoryInterface\AccessMobilePendingAuthRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AccessMobilePendingAuthEntity> */
final class AccessMobilePendingAuthRepository extends ServiceEntityRepository implements AccessMobilePendingAuthRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessMobilePendingAuthEntity::class);
    }

    public function save(AccessMobilePendingAuthEntity $pendingAuth, bool $flush = false): void
    {
        $this->getEntityManager()->persist($pendingAuth);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByTokenHash(string $tokenHash): ?AccessMobilePendingAuthEntity
    {
        $pendingAuth = $this->findOneBy(['tokenHash' => $tokenHash]);

        return $pendingAuth instanceof AccessMobilePendingAuthEntity ? $pendingAuth : null;
    }
}
