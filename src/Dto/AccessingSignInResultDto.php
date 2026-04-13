<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Dto;

use App\Entity\Account;

final readonly class AccessingSignInResultDto
{
    private function __construct(
        public bool $authenticated,
        public bool $requiresSecondFactor,
        public ?Account $account,
        public string $message,
    ) {}

    public static function authenticated(Account $account): self
    {
        return new self(true, false, $account, 'Signed in successfully.');
    }

    public static function pendingSecondFactor(Account $account): self
    {
        return new self(false, true, $account, 'Second factor verification is required.');
    }

    public static function failed(string $message): self
    {
        return new self(false, false, null, $message);
    }
}
