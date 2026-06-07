<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Dto;

use App\Accessing\Entity\AccessEntity;

final readonly class AccessSignInResultDto
{
    private function __construct(
        public bool $authenticated,
        public bool $requiresSecondFactor,
        public ?AccessEntity $user,
        public string $message,
    ) {
    }

    public static function authenticated(AccessEntity $user): self
    {
        return new self(true, false, $user, 'Signed in successfully.');
    }

    public static function pendingSecondFactor(AccessEntity $user): self
    {
        return new self(false, true, $user, 'Second factor verification is required.');
    }

    public static function failed(string $message): self
    {
        return new self(false, false, null, $message);
    }
}
