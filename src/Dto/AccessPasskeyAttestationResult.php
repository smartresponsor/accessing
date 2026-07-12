<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

final readonly class AccessPasskeyAttestationResult
{
    /** @param list<string> $transports */
    public function __construct(
        public string $credentialId,
        public string $userHandle,
        public string $publicKey,
        public array $transports,
        public int $signCount = 0,
        public ?string $credentialRecord = null,
    ) {
        if ('' === trim($credentialId) || '' === trim($userHandle) || '' === trim($publicKey)) {
            throw new \InvalidArgumentException('Verified passkey attestation material cannot be empty.');
        }

        if ($signCount < 0) {
            throw new \InvalidArgumentException('Verified passkey sign count cannot be negative.');
        }
    }
}
