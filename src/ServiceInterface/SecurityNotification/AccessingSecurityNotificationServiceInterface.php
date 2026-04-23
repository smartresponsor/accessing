<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\SecurityNotification;

use App\Accessing\Entity\AccessAccountEntity;

interface AccessingSecurityNotificationServiceInterface
{
    public function sendEmailVerificationCode(AccessAccountEntity $account, string $plainCode, int $ttlMinutes): void;

    public function sendPasswordRecoveryCode(AccessAccountEntity $account, string $plainCode, int $ttlMinutes): void;
}
