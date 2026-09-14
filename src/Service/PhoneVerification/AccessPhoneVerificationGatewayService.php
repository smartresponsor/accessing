<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\PhoneVerification;

use App\Accessing\ProviderInterface\PhoneVerification\AccessPhoneVerificationProviderInterface;

/**
 * Defines the phone verification gateway service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessPhoneVerificationGatewayService implements AccessPhoneVerificationProviderInterface
{
    /**
     * @param iterable<AccessPhoneVerificationProviderInterface> $providers
     */
    public function __construct(
        private iterable $providers,
        private string $selectedProvider,
    ) {
    }

    /**
     * Executes the supports operation within the canonical Accessing component workflow.
     */
    public function supports(string $providerName): bool
    {
        return $providerName === $this->selectedProvider;
    }

    /**
     * Executes the send verification message operation within the canonical Accessing component workflow.
     */
    public function sendVerificationMessage(string $phoneNumber, string $message): void
    {
        foreach ($this->providers as $provider) {
            if ($provider === $this) {
                continue;
            }

            if ($provider->supports($this->selectedProvider)) {
                $provider->sendVerificationMessage($phoneNumber, $message);

                return;
            }
        }

        throw new \RuntimeException(sprintf('No phone verification provider supports "%s".', $this->selectedProvider));
    }
}
