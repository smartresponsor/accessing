<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecurityNotification;

use App\Accessing\Entity\AccessUserEntity;

interface AccessSecurityNotificationServiceInterface
{
    public function sendEmailVerificationCode(AccessUserEntity $user, string $plainCode, int $ttlMinutes): void;

    public function sendPasswordRecoveryCode(AccessUserEntity $user, string $plainCode, int $ttlMinutes): void;
}
