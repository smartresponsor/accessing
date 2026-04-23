<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Dto;

use App\Accessing\Entity\AccessAccountEntity;

final readonly class AccessingSignInResultDto
{
    private function __construct(
        public bool $authenticated,
        public bool $requiresSecondFactor,
        public ?AccessAccountEntity $account,
        public string $message,
    ) {
    }

    public static function authenticated(AccessAccountEntity $account): self
    {
        return new self(true, false, $account, 'Signed in successfully.');
    }

    public static function pendingSecondFactor(AccessAccountEntity $account): self
    {
        return new self(false, true, $account, 'Second factor verification is required.');
    }

    public static function failed(string $message): self
    {
        return new self(false, false, null, $message);
    }
}
