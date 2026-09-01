<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Provider\PhoneVerification;

use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;

final class AccessNullPhoneVerificationProvider implements AccessPhoneVerificationProviderInterface
{
    public function supports(string $providerName): bool
    {
        return 'null' === $providerName;
    }

    public function sendVerificationMessage(string $phoneNumber, string $message): void
    {
    }
}
