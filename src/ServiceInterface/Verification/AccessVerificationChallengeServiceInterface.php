<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Verification;

use App\Accessing\DTO\AccessIssuedChallengeDTO;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the verification challenge service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessVerificationChallengeServiceInterface
{
    /**
     * Executes the issue email verification operation within the canonical Accessing component workflow.
     */
    public function issueEmailVerification(AccessEntity $user, ?Request $request = null): AccessIssuedChallengeDTO;

    /**
     * Executes the resend email verification operation within the canonical Accessing component workflow.
     */
    public function resendEmailVerification(AccessEntity $user, ?Request $request = null): ?AccessIssuedChallengeDTO;

    /**
     * Executes the issue phone verification operation within the canonical Accessing component workflow.
     */
    public function issuePhoneVerification(AccessEntity $user, string $phoneNumber, ?Request $request = null): AccessIssuedChallengeDTO;

    /**
     * Executes the issue password recovery operation within the canonical Accessing component workflow.
     */
    public function issuePasswordRecovery(AccessEntity $user, ?Request $request = null): AccessIssuedChallengeDTO;

    /**
     * Executes the complete email verification operation within the canonical Accessing component workflow.
     */
    public function completeEmailVerification(AccessEntity $user, string $code): bool;

    /**
     * Executes the complete phone verification operation within the canonical Accessing component workflow.
     */
    public function completePhoneVerification(AccessEntity $user, string $code): bool;

    /**
     * Executes the consume password recovery operation within the canonical Accessing component workflow.
     */
    public function consumePasswordRecovery(AccessEntity $user, string $code): bool;

    /**
     * Executes the cleanup expired challenges operation within the canonical Accessing component workflow.
     */
    public function cleanupExpiredChallenges(): int;
}
