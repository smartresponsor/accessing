<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ValueObject;

enum SecurityEventType: string
{
    case AccountRegistered = 'account_registered';
    case SignInSucceeded = 'sign_in_succeeded';
    case SignInFailed = 'sign_in_failed';
    case AccountLocked = 'account_locked';
    case AccountUnlocked = 'account_unlocked';
    case EmailVerificationRequested = 'email_verification_requested';
    case EmailVerified = 'email_verified';
    case PhoneVerificationRequested = 'phone_verification_requested';
    case PhoneVerified = 'phone_verified';
    case SecondFactorEnrolled = 'second_factor_enrolled';
    case SecondFactorChallenged = 'second_factor_challenged';
    case SecondFactorRevoked = 'second_factor_revoked';
    case RecoveryRequested = 'recovery_requested';
    case RecoveryCompleted = 'recovery_completed';
    case RecoveryCodeUsed = 'recovery_code_used';
    case SessionRegistered = 'session_registered';
    case SessionInvalidated = 'session_invalidated';
    case PasswordChanged = 'password_changed';
}
