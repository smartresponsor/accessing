<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe administrative action request for Accessing accounts.
 *
 * Administering may orchestrate these actions, but Accessing remains the owner
 * of account/session/security internals.
 */
final readonly class AccessingAccountAdministrationAction
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $action,
        private string $accountReference,
        private string $requestedBySubject,
        private array $safeContext = [],
    ) {
    }

    public function action(): string
    {
        return $this->action;
    }

    public function accountReference(): string
    {
        return $this->accountReference;
    }

    public function requestedBySubject(): string
    {
        return $this->requestedBySubject;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }
}
