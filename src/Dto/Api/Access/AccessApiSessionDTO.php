<?php

declare(strict_types=1);

namespace App\Accessing\DTO\Api\Access;

/**
 * Defines the api session dto type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessApiSessionDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        public string $status,
        public ?AccessApiIdentityDTO $identity = null,
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
        public ?string $expiresAt = null,
        public bool $requiresVerification = false,
        public bool $requiresSecondFactor = false,
        public ?string $pendingToken = null,
    ) {
    }

    /**
     * @return array{
     *     status: string,
     *     identity: array{userId: int|string|null, displayName: ?string, email: ?string, emailVerified: bool, secondFactorEnabled: bool}|null,
     *     accessToken: ?string,
     *     refreshToken: ?string,
     *     expiresAt: ?string,
     *     pendingToken: ?string,
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
            'pendingToken' => $this->pendingToken,
            'requiresVerification' => $this->requiresVerification,
            'requiresSecondFactor' => $this->requiresSecondFactor,
        ];
    }
}
