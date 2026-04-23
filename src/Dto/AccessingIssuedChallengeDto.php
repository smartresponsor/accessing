<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Dto;

use App\Accessing\Entity\AccessVerificationChallengeEntity;

final readonly class AccessingIssuedChallengeDto
{
    public function __construct(
        public AccessVerificationChallengeEntity $challenge,
        public string $plainCode,
    ) {
    }
}
