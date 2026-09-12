<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;

/**
 * Defines the credential repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessCredentialRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessCredentialEntity $credential, bool $flush = false): void;

    /**
     * Executes the find one for user operation within the canonical Accessing component workflow.
     */
    public function findOneForUser(AccessEntity $user): ?AccessCredentialEntity;
}
