<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Metadata-only report for controlled account-action audit.
 *
 * The report must never include password hashes, TOTP secrets, recovery codes,
 * reset tokens, raw session payloads, or verification internals.
 */
final readonly class AccessAccountAdministrationAuditReport
{
    /** @param list<AccessAccountAdministrationAuditProjection> $items */
    public function __construct(
        private AccessAccountAdministrationAuditFilter $filter,
        private AccessAccountAdministrationAuditSummary $summary,
        private array $items,
    ) {
    }

    public function filter(): AccessAccountAdministrationAuditFilter
    {
        return $this->filter;
    }

    public function summary(): AccessAccountAdministrationAuditSummary
    {
        return $this->summary;
    }

    /** @return list<AccessAccountAdministrationAuditProjection> */
    public function items(): array
    {
        return $this->items;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'filter' => $this->filter->toSafeArray(),
            'summary' => $this->summary->toSafeArray(),
            'items' => array_map(
                static fn (AccessAccountAdministrationAuditProjection $item): array => $item->toSafeArray(),
                $this->items,
            ),
        ];
    }
}
