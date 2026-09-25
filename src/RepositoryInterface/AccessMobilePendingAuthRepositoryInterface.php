<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessMobilePendingAuthEntity;

/**
 * Defines the mobile pending auth repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessMobilePendingAuthRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessMobilePendingAuthEntity $pendingAuth, bool $flush = false): void;

    /**
     * Executes the find one by token hash operation within the canonical Accessing component workflow.
     */
    public function findOneByTokenHash(string $tokenHash): ?AccessMobilePendingAuthEntity;
}
