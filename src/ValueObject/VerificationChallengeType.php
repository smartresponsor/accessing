<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ValueObject;

enum VerificationChallengeType: string
{
    case EmailVerification = 'email_verification';
    case PhoneVerification = 'phone_verification';
    case PasswordRecovery = 'password_recovery';
}
