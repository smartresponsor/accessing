<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessExternalIdentityEntity;

/**
 * Defines the external identity repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessExternalIdentityRepositoryInterface
{
    /**
     * Executes the find one by provider and subject operation within the canonical Accessing component workflow.
     */
    public function findOneByProviderAndSubject(string $provider, string $subject): ?AccessExternalIdentityEntity;

    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessExternalIdentityEntity $identity, bool $flush = false): void;
}
