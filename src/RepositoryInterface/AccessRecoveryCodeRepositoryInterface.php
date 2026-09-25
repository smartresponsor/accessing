<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;

/**
 * Defines the recovery code repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessRecoveryCodeRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessRecoveryCodeEntity $recoveryCode, bool $flush = false): void;

    /**
     * @return list<AccessRecoveryCodeEntity>
     */
    public function findActiveForUser(AccessEntity $user): array;

    /**
     * Executes the cleanup consumed before operation within the canonical Accessing component workflow.
     */
    public function cleanupConsumedBefore(\DateTimeImmutable $before): int;
}
