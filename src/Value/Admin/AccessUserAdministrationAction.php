<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe administrative action request for Accessing users.
 *
 * Administering may orchestrate these actions, but Accessing remains the owner
 * of user/session/security internals.
 */
final readonly class AccessUserAdministrationAction
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $action,
        private string $userReference,
        private string $requestedBySubject,
        private array $safeContext = [],
    ) {
    }

    public function action(): string
    {
        return $this->action;
    }

    public function userReference(): string
    {
        return $this->userReference;
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
