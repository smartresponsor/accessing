<?php

declare(strict_types=1);

namespace App\Dto;

use App\Entity\VerificationChallenge;

final readonly class AccessingIssuedChallengeDto
{
    public function __construct(
        public VerificationChallenge $challenge,
        public string $plainCode,
    ) {}
}
