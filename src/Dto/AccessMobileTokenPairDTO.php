<?php

declare(strict_types=1);

namespace App\Accessing\DTO;

/**
 * Defines the mobile token pair dto type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessMobileTokenPairDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(public string $accessToken, public string $refreshToken, public \DateTimeImmutable $accessExpiresAt, public \DateTimeImmutable $refreshExpiresAt, public string $sessionId)
    {
    }
}
