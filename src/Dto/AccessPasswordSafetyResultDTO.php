<?php

declare(strict_types=1);

namespace App\Accessing\DTO;

use App\Accessing\ValueObject\AccessPasswordSafetyStatus;

/**
 * Defines the password safety result dto type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessPasswordSafetyResultDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(public AccessPasswordSafetyStatus $status)
    {
    }
}
