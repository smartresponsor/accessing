<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ValueObject;

/**
 * Defines the email address type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessEmailAddress
{
    private string $value;

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));

        if ('' === $normalized) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid email address is required.');
        }

        $this->value = $normalized;
    }

    /**
     * Executes the to string operation within the canonical Accessing component workflow.
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Executes the __to string operation within the canonical Accessing component workflow.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
