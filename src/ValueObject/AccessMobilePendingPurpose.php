<?php

declare(strict_types=1);

namespace App\Accessing\ValueObject;

/**
 * Defines the mobile pending purpose type and its canonical responsibility within the Accessing component.
 */
enum AccessMobilePendingPurpose: string
{
    case EmailVerification = 'email_verification';
    case SecondFactor = 'second_factor';
}
