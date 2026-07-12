<?php

declare(strict_types=1);

namespace App\Accessing\Repository;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyCredentialRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AccessPasskeyCredentialEntity> */
final class AccessPasskeyCredentialRepository extends ServiceEntityRepository implements AccessPasskeyCredentialRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessPasskeyCredentialEntity::class);
    }

    public function save(AccessPasskeyCredentialEntity $credential, bool $flush = false): void
    {
        $this->getEntityManager()->persist($credential);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByCredentialId(string $credentialId): ?AccessPasskeyCredentialEntity
    {
        $credential = $this->findOneBy(['credentialId' => $credentialId]);

        return $credential instanceof AccessPasskeyCredentialEntity ? $credential : null;
    }

    public function findActiveForUser(AccessEntity $user): array
    {
        return array_values(array_filter(
            $this->findBy(['user' => $user], ['createdAt' => 'DESC']),
            static fn (AccessPasskeyCredentialEntity $credential): bool => $credential->isActive(),
        ));
    }
}
