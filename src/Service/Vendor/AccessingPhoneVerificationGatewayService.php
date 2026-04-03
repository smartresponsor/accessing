<?php

declare(strict_types=1);

namespace App\Service\Vendor;

use App\ServiceInterface\Vendor\AccessingPhoneVerificationProviderServiceInterface;

final readonly class AccessingPhoneVerificationGatewayService implements AccessingPhoneVerificationProviderServiceInterface
{
    /**
     * @param iterable<AccessingPhoneVerificationProviderServiceInterface> $providers
     */
    public function __construct(
        private iterable $providers,
        private string $selectedProvider,
    ) {}

    public function supports(string $providerName): bool
    {
        return $providerName === $this->selectedProvider;
    }

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
