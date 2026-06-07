<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Higher-level request DTO for controlled user administration actions.
 *
 * The request may be created by Administering, but Accessing remains the owner
 * of user/session/security internals and decides whether execution is allowed.
 */
final readonly class AccessUserAdministrationRequest
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $action,
        private string $userReference,
        private string $requestedBySubject,
        private string $safeReason = 'administrative request',
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

    public function safeReason(): string
    {
        return $this->safeReason;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    public function toAction(): AccessUserAdministrationAction
    {
        return new AccessUserAdministrationAction(
            $this->action,
            $this->userReference,
            $this->requestedBySubject,
            $this->safeContext + ['safe_reason' => $this->safeReason],
        );
    }
}
