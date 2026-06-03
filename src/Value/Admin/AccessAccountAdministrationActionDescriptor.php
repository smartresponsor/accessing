<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe metadata describing controlled Accessing account administration actions.
 */
final class AccessAccountAdministrationActionDescriptor
{
    public function __construct(
        private readonly string $key,
        private readonly string $label,
        private readonly string $riskLevel,
        private readonly bool $requiresReason = true,
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function riskLevel(): string
    {
        return $this->riskLevel;
    }

    public function requiresReason(): bool
    {
        return $this->requiresReason;
    }
}
