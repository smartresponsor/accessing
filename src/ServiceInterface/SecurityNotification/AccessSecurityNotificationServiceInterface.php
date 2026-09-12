<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecurityNotification;

use App\Accessing\Entity\AccessEntity;

/**
 * Defines the security notification service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessSecurityNotificationServiceInterface
{
    /**
     * Executes the send email verification code operation within the canonical Accessing component workflow.
     */
    public function sendEmailVerificationCode(AccessEntity $user, string $plainCode, int $ttlMinutes): void;

    /**
     * Executes the send password recovery code operation within the canonical Accessing component workflow.
     */
    public function sendPasswordRecoveryCode(AccessEntity $user, string $plainCode, int $ttlMinutes): void;

    /**
     * Executes the send password reset link operation within the canonical Accessing component workflow.
     */
    public function sendPasswordResetLink(
        AccessEntity $user,
        string $resetUrl,
        \DateTimeImmutable $expiresAt,
    ): void;
}
