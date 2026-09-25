<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ProviderInterface\PhoneVerification;

/**
 * Defines the phone verification provider interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPhoneVerificationProviderInterface
{
    /**
     * Executes the supports operation within the canonical Accessing component workflow.
     */
    public function supports(string $providerName): bool;

    /**
     * Executes the send verification message operation within the canonical Accessing component workflow.
     */
    public function sendVerificationMessage(string $phoneNumber, string $message): void;
}
