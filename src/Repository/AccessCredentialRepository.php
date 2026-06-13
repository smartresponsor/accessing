<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessCredentialRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AccessCredentialEntity>
 */
final class AccessCredentialRepository extends ServiceEntityRepository implements AccessCredentialRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessCredentialEntity::class);
    }

    public function save(AccessCredentialEntity $credential, bool $flush = false): void
    {
        $this->getEntityManager()->persist($credential);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneForUser(AccessEntity $user): ?AccessCredentialEntity
    {
        $credential = $this->findOneBy(['user' => $user]);

        return $credential instanceof AccessCredentialEntity ? $credential : null;
    }
}
