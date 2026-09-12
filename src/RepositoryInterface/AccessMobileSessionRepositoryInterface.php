<?php

declare(strict_types=1);

namespace App\Accessing\RepositoryInterface;

use App\Accessing\Entity\AccessMobileSessionEntity;

/**
 * Defines the mobile session repository interface type and its canonical responsibility within the Accessing component.
 */
interface AccessMobileSessionRepositoryInterface
{
    /**
     * Executes the save operation within the canonical Accessing component workflow.
     */
    public function save(AccessMobileSessionEntity $session, bool $flush = false): void;

    /**
     * Executes the find one by access token hash operation within the canonical Accessing component workflow.
     */
    public function findOneByAccessTokenHash(string $tokenHash): ?AccessMobileSessionEntity;

    /**
     * Executes the find one by refresh token hash operation within the canonical Accessing component workflow.
     */
    public function findOneByRefreshTokenHash(string $tokenHash): ?AccessMobileSessionEntity;

    /**
     * Executes the find one by previous refresh token hash operation within the canonical Accessing component workflow.
     */
    public function findOneByPreviousRefreshTokenHash(string $tokenHash): ?AccessMobileSessionEntity;
}
