<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface;

use App\Accessing\DTO\AccessSignInResultDTO;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Defines the authentication service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessAuthenticationServiceInterface
{
    /**
     * Executes the attempt password sign in operation within the canonical Accessing component workflow.
     */
    public function attemptPasswordSignIn(string $emailAddress, string $plainPassword, Request $request): AccessSignInResultDTO;

    /**
     * Executes the complete pending second factor operation within the canonical Accessing component workflow.
     */
    public function completePendingSecondFactor(AccessEntity $user, Request $request): void;

    /**
     * Executes the complete mobile second factor operation within the canonical Accessing component workflow.
     */
    public function completeMobileSecondFactor(AccessEntity $user, Request $request): void;

    /**
     * Executes the complete passkey sign in operation within the canonical Accessing component workflow.
     */
    public function completePasskeySignIn(AccessEntity $user, Request $request): void;

    /**
     * Executes the complete external sign in operation within the canonical Accessing component workflow.
     */
    public function completeExternalSignIn(AccessEntity $user, Request $request): void;

    /**
     * Executes the sign out operation within the canonical Accessing component workflow.
     */
    public function signOut(?AccessEntity $user, Request $request): void;

    /**
     * Executes the get pending second factor user id operation within the canonical Accessing component workflow.
     */
    public function getPendingSecondFactorUserId(SessionInterface $session): ?int;

    /**
     * Executes the clear pending second factor operation within the canonical Accessing component workflow.
     */
    public function clearPendingSecondFactor(SessionInterface $session): void;
}
