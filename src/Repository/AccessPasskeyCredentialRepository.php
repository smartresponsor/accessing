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
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessPasskeyCredentialEntity::class);
    }

    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessPasskeyCredentialEntity $credential, bool $flush = false): void
    {
        $this->getEntityManager()->persist($credential);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Executes the find one by credential id operation within the canonical Accessing component workflow.
     */
    public function findOneByCredentialId(string $credentialId): ?AccessPasskeyCredentialEntity
    {
        $credential = $this->findOneBy(['credentialId' => $credentialId]);

        return $credential instanceof AccessPasskeyCredentialEntity ? $credential : null;
    }

    /**
     * Executes the find active for user operation within the canonical Accessing component workflow.
     */
    public function findActiveForUser(AccessEntity $user): array
    {
        return array_values(array_filter(
            $this->findBy(['user' => $user], ['createdAt' => 'DESC']),
            static fn (AccessPasskeyCredentialEntity $credential): bool => $credential->isActive(),
        ));
    }
}
