<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Vendor;

interface AccessingPhoneVerificationProviderServiceInterface
{
    public function supports(string $providerName): bool;

    public function sendVerificationMessage(string $phoneNumber, string $message): void;
}
