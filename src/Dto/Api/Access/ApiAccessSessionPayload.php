<?php

declare(strict_types=1);

namespace App\Accessing\Dto\Api\Access;

final readonly class ApiAccessSessionPayload
{
    public function __construct(
        public string $status,
        public ?ApiAccessIdentityPayload $identity = null,
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
        public ?string $expiresAt = null,
        public bool $requiresVerification = false,
        public bool $requiresSecondFactor = false,
    ) {
    }

    /**
     * @return array{
     *     status: string,
     *     identity: array{userId: int|string|null, displayName: ?string, email: ?string, emailVerified: bool, secondFactorEnabled: bool}|null,
     *     accessToken: ?string,
     *     refreshToken: ?string,
     *     expiresAt: ?string,
     *     requiresVerification: bool,
     *     requiresSecondFactor: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'identity' => $this->identity?->toArray(),
            'accessToken' => $this->accessToken,
            'refreshToken' => $this->refreshToken,
            'expiresAt' => $this->expiresAt,
            'requiresVerification' => $this->requiresVerification,
            'requiresSecondFactor' => $this->requiresSecondFactor,
        ];
    }
}
