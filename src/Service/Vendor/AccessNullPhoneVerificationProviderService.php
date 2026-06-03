<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Vendor;

use App\Accessing\ServiceInterface\Vendor\AccessPhoneVerificationProviderServiceInterface;

final class AccessNullPhoneVerificationProviderService implements AccessPhoneVerificationProviderServiceInterface
{
    public function supports(string $providerName): bool
    {
        return 'null' === $providerName;
    }

    public function sendVerificationMessage(string $phoneNumber, string $message): void
    {
    }
}
