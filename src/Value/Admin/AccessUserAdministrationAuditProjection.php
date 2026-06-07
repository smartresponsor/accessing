<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Metadata-only projection of controlled Accessing user administration audit.
 *
 * This projection is safe for Administering. It must not expose password hashes,
 * TOTP secrets, recovery codes, reset tokens, raw sessions, or verification
 * internals.
 */
final readonly class AccessUserAdministrationAuditProjection
{
    /** @param array<string, mixed> $safeContext */
    public function __construct(
        private string $action,
        private string $userReference,
        private string $requestedBySubject,
        private string $resultStatus,
        private string $safeMessage,
        private \DateTimeImmutable $createdAt,
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

    public function resultStatus(): string
    {
        return $this->resultStatus;
    }

    public function safeMessage(): string
    {
        return $this->safeMessage;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return array<string, mixed> */
    public function safeContext(): array
    {
        return $this->safeContext;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'action' => $this->action,
            'userReference' => $this->userReference,
            'requestedBySubject' => $this->requestedBySubject,
            'resultStatus' => $this->resultStatus,
            'safeMessage' => $this->safeMessage,
            'createdAt' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'safeContext' => $this->safeContext,
        ];
    }
}
