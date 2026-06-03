<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Higher-level request DTO for controlled account administration actions.
 *
 * The request may be created by Administering, but Accessing remains the owner
 * of account/session/security internals and decides whether execution is allowed.
 */
final readonly class AccessAccountAdministrationRequest
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $action,
        private string $accountReference,
        private string $requestedBySubject,
        private string $safeReason = 'administrative request',
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

    public function safeReason(): string
    {
        return $this->safeReason;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    public function toAction(): AccessAccountAdministrationAction
    {
        return new AccessAccountAdministrationAction(
            $this->action,
            $this->accountReference,
            $this->requestedBySubject,
            $this->safeContext + ['safe_reason' => $this->safeReason],
        );
    }
}
