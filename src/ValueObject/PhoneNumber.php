<?php

declare(strict_types=1);

namespace App\ValueObject;

use InvalidArgumentException;

final readonly class PhoneNumber
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        $hasPlus = str_starts_with($trimmed, '+');
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if ($digits === '') {
            throw new InvalidArgumentException('A phone number is required.');
        }

        if ($hasPlus) {
            $digits = '+' . $digits;
        }

        $digitCount = strlen(ltrim($digits, '+'));

        if ($digitCount < 10 || $digitCount > 15) {
            throw new InvalidArgumentException('Phone numbers must contain between 10 and 15 digits.');
        }

        $this->value = $hasPlus ? $digits : '+1' . $digits;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
