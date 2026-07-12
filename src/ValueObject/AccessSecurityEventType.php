<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ValueObject;

enum AccessSecurityEventType: string
{
    case UserRegistered = 'user_registered';
    case SignInSucceeded = 'sign_in_succeeded';
    case SignInFailed = 'sign_in_failed';
    case LockedAccountSignInAttempt = 'locked_account_sign_in_attempt';
    case UserLocked = 'user_locked';
    case UserUnlocked = 'user_unlocked';
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
    case RateLimitExceeded = 'rate_limit_exceeded';
    case VerificationAttemptLimitReached = 'verification_attempt_limit_reached';
    case NotificationDeliveryFailed = 'notification_delivery_failed';
    case PasskeyRegistered = 'passkey_registered';
    case PasskeyAuthenticated = 'passkey_authenticated';
    case PasskeyRevoked = 'passkey_revoked';
    case PasskeyCounterRegression = 'passkey_counter_regression';
    case MobileSessionIssued = 'mobile_session_issued';
    case MobileSessionRefreshed = 'mobile_session_refreshed';
    case MobileSessionRevoked = 'mobile_session_revoked';
    case MobileRefreshReuseDetected = 'mobile_refresh_reuse_detected';
}
