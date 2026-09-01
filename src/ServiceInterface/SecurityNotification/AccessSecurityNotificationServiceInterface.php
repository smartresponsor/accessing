<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecurityNotification;

use App\Accessing\Entity\AccessEntity;

interface AccessSecurityNotificationServiceInterface
{
    public function sendEmailVerificationCode(AccessEntity $user, string $plainCode, int $ttlMinutes): void;

    public function sendPasswordRecoveryCode(AccessEntity $user, string $plainCode, int $ttlMinutes): void;

    public function sendPasswordResetLink(
        AccessEntity $user,
        string $resetUrl,
        \DateTimeImmutable $expiresAt,
    ): void;
}
