<?php

declare(strict_types=1);

namespace App\Accessing\Dto\AccountContext;

final class AccessingCurrentAccountContext
{
    /** @param list<string> $bootstrapRoles */
    public function __construct(
        private readonly int|string $accountId,
        private readonly string $userIdentifier,
        private readonly ?string $displayName,
        private readonly array $bootstrapRoles,
        private readonly bool $locked,
        private readonly bool $emailVerified,
        private readonly bool $secondFactorEnabled,
    ) {
    }

    public function accountId(): int|string
    {
        return $this->accountId;
    }

    public function subjectIdentifier(): string
    {
        return 'accessing:account:'.(string) $this->accountId;
    }

    public function userIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }

    /** @return list<string> */
    public function bootstrapRoles(): array
    {
        return $this->bootstrapRoles;
    }

    public function locked(): bool
    {
        return $this->locked;
    }

    public function emailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function secondFactorEnabled(): bool
    {
        return $this->secondFactorEnabled;
    }
}
