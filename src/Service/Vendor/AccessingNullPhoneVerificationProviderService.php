<?php

declare(strict_types=1);

namespace App\Service\Vendor;

use App\ServiceInterface\Vendor\AccessingPhoneVerificationProviderServiceInterface;

final class AccessingNullPhoneVerificationProviderService implements AccessingPhoneVerificationProviderServiceInterface
{
    public function supports(string $providerName): bool
    {
        return $providerName === 'null';
    }

    public function sendVerificationMessage(string $phoneNumber, string $message): void {}
}
