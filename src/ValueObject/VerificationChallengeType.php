<?php

declare(strict_types=1);

namespace App\ValueObject;

enum VerificationChallengeType: string
{
    case EmailVerification = 'email_verification';
    case PhoneVerification = 'phone_verification';
    case PasswordRecovery = 'password_recovery';
}
