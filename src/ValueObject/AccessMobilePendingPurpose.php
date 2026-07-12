<?php

declare(strict_types=1);

namespace App\Accessing\ValueObject;

enum AccessMobilePendingPurpose: string
{
    case EmailVerification = 'email_verification';
    case SecondFactor = 'second_factor';
}
