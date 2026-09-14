<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSessionEntity;

/**
 * Defines the session repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessSessionRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessSessionEntity $userSession, bool $flush = false): void;

    /**
     * Executes the find one by session identifier operation within the canonical Accessing component workflow.
     */
    public function findOneBySessionIdentifier(string $sessionIdentifier): ?AccessSessionEntity;

    /**
     * @return list<AccessSessionEntity>
     */
    public function findActiveForUser(AccessEntity $user): array;

    /**
     * Executes the invalidate other active sessions operation within the canonical Accessing component workflow.
     */
    public function invalidateOtherActiveSessions(AccessEntity $user, string $keepSessionIdentifier): int;

    /**
     * Executes the cleanup invalidated before operation within the canonical Accessing component workflow.
     */
    public function cleanupInvalidatedBefore(\DateTimeImmutable $before): int;
}
