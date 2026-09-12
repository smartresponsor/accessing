<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ValueObject;

/**
 * Defines the phone number type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessPhoneNumber
{
    private string $value;

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        $hasPlus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ('' === $digits) {
            throw new \InvalidArgumentException('A phone number is required.');
        }

        if ($hasPlus) {
            $digits = '+'.$digits;
        }

        $digitCount = strlen(ltrim($digits, '+'));

        if ($digitCount < 10 || $digitCount > 15) {
            throw new \InvalidArgumentException('Phone numbers must contain between 10 and 15 digits.');
        }

        $this->value = $hasPlus ? $digits : '+1'.$digits;
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
