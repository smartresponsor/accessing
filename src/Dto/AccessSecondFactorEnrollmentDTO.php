<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

/**
 * Defines the second factor enrollment dto type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSecondFactorEnrollmentDTO
{
    /**
     * @param list<string> $recoveryCodes
     */
    public function __construct(
        public string $secret,
        public string $provisioningUri,
        public array $recoveryCodes = [],
    ) {
    }
}
