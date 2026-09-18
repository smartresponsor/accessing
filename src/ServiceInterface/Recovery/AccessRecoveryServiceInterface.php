<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Recovery;

use App\Accessing\DTO\AccessIssuedChallengeDTO;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the recovery service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessRecoveryServiceInterface
{
    /**
     * Executes the request password recovery operation within the canonical Accessing component workflow.
     */
    public function requestPasswordRecovery(string $emailAddress, ?Request $request = null): ?AccessIssuedChallengeDTO;

    /**
     * Executes the reset password operation within the canonical Accessing component workflow.
     */
    public function resetPassword(string $emailAddress, string $code, string $newPassword): bool;
}
