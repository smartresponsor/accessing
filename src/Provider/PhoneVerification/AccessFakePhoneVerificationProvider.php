<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Provider\PhoneVerification;

use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;
use Psr\Log\LoggerInterface;

final readonly class AccessFakePhoneVerificationProvider implements AccessPhoneVerificationProviderInterface
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function supports(string $providerName): bool
    {
        return '' === $providerName || 'fake' === $providerName;
    }

    public function sendVerificationMessage(string $phoneNumber, string $message): void
    {
        $this->logger->info('Fake phone verification dispatched.', [
            'phoneNumber' => $phoneNumber,
            'message' => $message,
        ]);
    }
}
