<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe remediation plan for Accessing administration readiness.
 */
final readonly class AccessAccountAdministrationRemediationPlan
{
    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $items,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function items(): array
    {
        return $this->items;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
            'items' => $this->items,
        ];
    }
}
