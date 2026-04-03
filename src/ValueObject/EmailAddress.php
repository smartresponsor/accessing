<?php

declare(strict_types=1);

namespace App\ValueObject;

use InvalidArgumentException;

final readonly class EmailAddress
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));

        if ($normalized === '' || !filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('A valid email address is required.');
        }

        $this->value = $normalized;
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
