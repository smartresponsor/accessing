<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Provider\PhoneVerification;

use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;

/**
 * Defines the null phone verification provider type and its canonical responsibility within the Accessing component.
 */
final class AccessNullPhoneVerificationProvider implements AccessPhoneVerificationProviderInterface
{
    /**
     * Executes the supports operation within the canonical Accessing component workflow.
     */
    public function supports(string $providerName): bool
    {
        return 'null' === $providerName;
    }

    /**
     * Executes the send verification message operation within the canonical Accessing component workflow.
     */
    public function sendVerificationMessage(string $phoneNumber, string $message): void
    {
    }
}
