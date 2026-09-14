<?php

declare(strict_types=1);

namespace App\Accessing\DTO;

/**
 * Defines the mobile pending token dto type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessMobilePendingTokenDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(public string $token, public \DateTimeImmutable $expiresAt)
    {
    }
}
