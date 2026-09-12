<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\DTO;

use App\Accessing\Entity\AccessEntity;

/**
 * Defines the sign in result dto type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSignInResultDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    private function __construct(
        public bool $authenticated,
        public bool $requiresSecondFactor,
        public ?AccessEntity $user,
        public string $message,
    ) {
    }

    /**
     * Executes the authenticated operation within the canonical Accessing component workflow.
     */
    public static function authenticated(AccessEntity $user): self
    {
        return new self(true, false, $user, 'Signed in successfully.');
    }

    /**
     * Executes the pending second factor operation within the canonical Accessing component workflow.
     */
    public static function pendingSecondFactor(AccessEntity $user): self
    {
        return new self(false, true, $user, 'Second factor verification is required.');
    }

    /**
     * Executes the failed operation within the canonical Accessing component workflow.
     */
    public static function failed(string $message): self
    {
        return new self(false, false, null, $message);
    }
}
