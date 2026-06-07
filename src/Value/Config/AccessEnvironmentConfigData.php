<?php

declare(strict_types=1);

namespace App\Accessing\Value\Config;

final class AccessEnvironmentConfigData
{
    public string $mailerSender = 'no-reply@example.test';

    public string $phoneVerificationProvider = 'fake';

    public string $sessionMaxIdleDays = '30';

    public string $recoveryCodeTtlMinutes = '30';

    public string $verificationCodeTtlMinutes = '10';

    public string $userLockThreshold = '5';

    public string $userLockMinutes = '15';
}
