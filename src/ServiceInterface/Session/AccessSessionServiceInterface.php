<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Session;

use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Defines the session service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessSessionServiceInterface
{
    /**
     * Executes the register session operation within the canonical Accessing component workflow.
     */
    public function registerSession(AccessEntity $user, Request $request, bool $flush = true): void;

    /**
     * Executes the invalidate current session operation within the canonical Accessing component workflow.
     */
    public function invalidateCurrentSession(AccessEntity $user, SessionInterface $session): void;

    /**
     * Executes the invalidate other sessions operation within the canonical Accessing component workflow.
     */
    public function invalidateOtherSessions(AccessEntity $user, SessionInterface $session): int;

    /**
     * Executes the cleanup sessions operation within the canonical Accessing component workflow.
     */
    public function cleanupSessions(): int;
}
