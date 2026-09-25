<?php

declare(strict_types=1);

namespace App\Accessing\Context;

/**
 * Defines the current context type and its canonical responsibility within the Accessing component.
 */
final class AccessCurrentContext
{
    /** @param list<string> $bootstrapRoles */
    public function __construct(
        private readonly int|string $userId,
        private readonly string $userIdentifier,
        private readonly ?string $displayName,
        private readonly array $bootstrapRoles,
        private readonly bool $locked,
        private readonly bool $emailVerified,
        private readonly bool $secondFactorEnabled,
    ) {
    }

    /**
     * Executes the user id operation within the canonical Accessing component workflow.
     */
    public function userId(): int|string
    {
        return $this->userId;
    }

    /**
     * Executes the subject identifier operation within the canonical Accessing component workflow.
     */
    public function subjectIdentifier(): string
    {
        return 'accessing:user:'.(string) $this->userId;
    }

    /**
     * Executes the user identifier operation within the canonical Accessing component workflow.
     */
    public function userIdentifier(): string
    {
        return $this->userIdentifier;
    }

    /**
     * Executes the display name operation within the canonical Accessing component workflow.
     */
    public function displayName(): ?string
    {
        return $this->displayName;
    }

    /** @return list<string> */
    public function bootstrapRoles(): array
    {
        return $this->bootstrapRoles;
    }

    /**
     * Executes the locked operation within the canonical Accessing component workflow.
     */
    public function locked(): bool
    {
        return $this->locked;
    }

    /**
     * Executes the email verified operation within the canonical Accessing component workflow.
     */
    public function emailVerified(): bool
    {
        return $this->emailVerified;
    }

    /**
     * Executes the second factor enabled operation within the canonical Accessing component workflow.
     */
    public function secondFactorEnabled(): bool
    {
        return $this->secondFactorEnabled;
    }
}
