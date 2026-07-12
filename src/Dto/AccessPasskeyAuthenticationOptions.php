<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

final readonly class AccessPasskeyAuthenticationOptions
{
    /** @param list<array{type: string, id: string, transports: list<string>}> $allowCredentials */
    public function __construct(
        public string $challenge,
        public string $relyingPartyId,
        public array $allowCredentials,
        public int $timeout = 300000,
        public string $userVerification = 'preferred',
    ) {
        if ('' === trim($challenge) || '' === trim($relyingPartyId)) {
            throw new \InvalidArgumentException('Passkey authentication options cannot contain empty identifiers.');
        }
    }

    /** @return array{publicKey: array{challenge: string, rpId: string, allowCredentials: list<array{type: string, id: string, transports: list<string>}>, timeout: int, userVerification: string}} */
    public function toArray(): array
    {
        return [
            'publicKey' => [
                'challenge' => $this->challenge,
                'rpId' => $this->relyingPartyId,
                'allowCredentials' => $this->allowCredentials,
                'timeout' => $this->timeout,
                'userVerification' => $this->userVerification,
            ],
        ];
    }
}
