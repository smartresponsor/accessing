<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe diagnostic issue for Accessing account administration.
 */
final readonly class AccessAccountAdministrationDiagnosticIssue
{
    /** @param array<string, mixed> $context */
    public function __construct(
        private string $key,
        private string $label,
        private string $category,
        private string $severity,
        private string $status,
        private bool $blocking,
        private array $context = [],
    ) {
    }

    public function severity(): string
    {
        return $this->severity;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function blocking(): bool
    {
        return $this->blocking;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'category' => $this->category,
            'severity' => $this->severity,
            'status' => $this->status,
            'blocking' => $this->blocking,
            'context' => $this->context,
        ];
    }
}
