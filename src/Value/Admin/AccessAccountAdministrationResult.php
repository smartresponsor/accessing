<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe result for controlled Accessing account administration actions.
 */
final readonly class AccessAccountAdministrationResult
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private bool $succeeded,
        private string $status,
        private string $safeMessage,
        private array $safeContext = [],
    ) {
    }

    /** @param array<string, mixed> $safeContext */
    public static function success(string $safeMessage = 'Account administration action accepted.', array $safeContext = []): self
    {
        return new self(true, 'succeeded', $safeMessage, $safeContext);
    }

    /** @param array<string, mixed> $safeContext */
    public static function rejected(string $safeMessage, array $safeContext = []): self
    {
        return new self(false, 'rejected', $safeMessage, $safeContext);
    }

    public function succeeded(): bool
    {
        return $this->succeeded;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function safeMessage(): string
    {
        return $this->safeMessage;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }
}
