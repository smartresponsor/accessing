<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

use App\Accessing\Entity\AccessVerificationChallengeEntity;

/**
 * Defines the issued challenge dto type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessIssuedChallengeDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        public AccessVerificationChallengeEntity $challenge,
        public string $plainCode,
    ) {
    }
}
