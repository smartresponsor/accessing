<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Metadata-only capability descriptor for Accessing administration surfaces.
 */
final readonly class AccessAccountAdministrationCapabilityDescriptor
{
    /** @param array<string, mixed> $context */
    public function __construct(
        private string $key,
        private string $label,
        private string $category,
        private string $status,
        private bool $sensitive,
        private bool $executable,
        private bool $requiresReview,
        private array $context = [],
    ) {
    }

    public function key(): string
    {
        return $this->key;
    }

    public function category(): string
    {
        return $this->category;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function executable(): bool
    {
        return $this->executable;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'category' => $this->category,
            'status' => $this->status,
            'sensitive' => $this->sensitive,
            'executable' => $this->executable,
            'requiresReview' => $this->requiresReview,
            'context' => $this->context,
        ];
    }
}
