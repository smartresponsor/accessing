<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

final readonly class AccessMobileTokenPair
{
    public function __construct(public string $accessToken, public string $refreshToken, public \DateTimeImmutable $accessExpiresAt, public \DateTimeImmutable $refreshExpiresAt, public string $sessionId)
    {
    }
}
