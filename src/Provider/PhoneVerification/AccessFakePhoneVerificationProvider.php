<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Provider\PhoneVerification;

use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;
use Psr\Log\LoggerInterface;

/**
 * Defines the fake phone verification provider type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessFakePhoneVerificationProvider implements AccessPhoneVerificationProviderInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Executes the supports operation within the canonical Accessing component workflow.
     */
    public function supports(string $providerName): bool
    {
        return '' === $providerName || 'fake' === $providerName;
    }

    /**
     * Executes the send verification message operation within the canonical Accessing component workflow.
     */
    public function sendVerificationMessage(string $phoneNumber, string $message): void
    {
        $this->logger->info('Fake phone verification dispatched.', [
            'phoneNumber' => $phoneNumber,
            'message' => $message,
        ]);
    }
}
