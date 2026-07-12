<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

final readonly class AccessMobilePendingToken
{
    public function __construct(public string $token, public \DateTimeImmutable $expiresAt)
    {
    }
}
