<?php

declare(strict_types=1);

namespace App\Dto;

final readonly class AccessingSecondFactorEnrollmentDto
{
    /**
     * @param list<string> $recoveryCodes
     */
    public function __construct(
        public string $secret,
        public string $provisioningUri,
        public array $recoveryCodes = [],
    ) {}
}
