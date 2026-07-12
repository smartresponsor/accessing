<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

use App\Accessing\ValueObject\AccessPasswordSafetyStatus;

final readonly class AccessPasswordSafetyResult
{
    public function __construct(public AccessPasswordSafetyStatus $status)
    {
    }
}
