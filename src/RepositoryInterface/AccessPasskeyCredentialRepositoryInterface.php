<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;

/**
 * Defines the passkey credential repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPasskeyCredentialRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessPasskeyCredentialEntity $credential, bool $flush = false): void;

    /**
     * Executes the find one by credential id operation within the canonical Accessing component workflow.
     */
    public function findOneByCredentialId(string $credentialId): ?AccessPasskeyCredentialEntity;

    /** @return list<AccessPasskeyCredentialEntity> */
    public function findActiveForUser(AccessEntity $user): array;
}
