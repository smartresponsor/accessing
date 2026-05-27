<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe account projection for Administering UI.
 *
 * This value deliberately excludes password hashes, TOTP secrets, recovery codes,
 * reset tokens, verification internals, and raw session payloads.
 */
final class AccessingAccountAdministrationProjection
{
    /** @param list<string> $bootstrapRoles */
    public function __construct(
        private readonly string $subjectId,
        private readonly string $identifier,
        private readonly bool $active,
        private readonly bool $verified,
        private readonly array $bootstrapRoles = [],
        private readonly ?string $displayName = null,
    ) {
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function active(): bool
    {
        return $this->active;
    }

    public function verified(): bool
    {
        return $this->verified;
    }

    /** @return list<string> */
    public function bootstrapRoles(): array
    {
        return $this->bootstrapRoles;
    }

    public function displayName(): ?string
    {
        return $this->displayName;
    }
}
