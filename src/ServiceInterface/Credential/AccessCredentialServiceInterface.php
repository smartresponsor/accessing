<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Credential;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;

/**
 * Defines the credential service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessCredentialServiceInterface
{
    /**
     * Executes the create credential operation within the canonical Accessing component workflow.
     */
    public function createCredential(AccessEntity $user, string $plainPassword): AccessCredentialEntity;

    /**
     * Executes the verify password operation within the canonical Accessing component workflow.
     */
    public function verifyPassword(AccessEntity $user, string $plainPassword): bool;

    /**
     * Executes the change password operation within the canonical Accessing component workflow.
     */
    public function changePassword(AccessEntity $user, string $plainPassword): void;
}
