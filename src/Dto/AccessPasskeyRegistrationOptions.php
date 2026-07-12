<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

final readonly class AccessPasskeyRegistrationOptions
{
    /**
     * @param array{id: string, name: string}                                 $relyingParty
     * @param array{id: string, name: string, displayName: string}            $user
     * @param list<array{type: string, alg: int}>                             $pubKeyCredParams
     * @param list<array{type: string, id: string, transports: list<string>}> $excludeCredentials
     */
    public function __construct(
        public string $challenge,
        public array $relyingParty,
        public array $user,
        public array $pubKeyCredParams,
        public array $excludeCredentials,
        public int $timeout,
        public string $attestation = 'none',
        public string $residentKey = 'preferred',
        public string $userVerification = 'preferred',
    ) {
    }

    /**
     * @return array{publicKey: array{
     *     challenge: string,
     *     rp: array{id: string, name: string},
     *     user: array{id: string, name: string, displayName: string},
     *     pubKeyCredParams: list<array{type: string, alg: int}>,
     *     excludeCredentials: list<array{type: string, id: string, transports: list<string>}>,
     *     timeout: int,
     *     attestation: string,
     *     authenticatorSelection: array{residentKey: string, userVerification: string}
     * }}
     */
    public function toArray(): array
    {
        return [
            'publicKey' => [
                'challenge' => $this->challenge,
                'rp' => $this->relyingParty,
                'user' => $this->user,
                'pubKeyCredParams' => $this->pubKeyCredParams,
                'excludeCredentials' => $this->excludeCredentials,
                'timeout' => $this->timeout,
                'attestation' => $this->attestation,
                'authenticatorSelection' => [
                    'residentKey' => $this->residentKey,
                    'userVerification' => $this->userVerification,
                ],
            ],
        ];
    }
}
