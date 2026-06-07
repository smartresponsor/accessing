<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Metadata-only report for controlled user-action audit.
 *
 * The report must never include password hashes, TOTP secrets, recovery codes,
 * reset tokens, raw session payloads, or verification internals.
 */
final readonly class AccessUserAdministrationAuditReport
{
    /** @param list<AccessUserAdministrationAuditProjection> $items */
    public function __construct(
        private AccessUserAdministrationAuditFilter $filter,
        private AccessUserAdministrationAuditSummary $summary,
        private array $items,
    ) {
    }

    public function filter(): AccessUserAdministrationAuditFilter
    {
        return $this->filter;
    }

    public function summary(): AccessUserAdministrationAuditSummary
    {
        return $this->summary;
    }

    /** @return list<AccessUserAdministrationAuditProjection> */
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
                static fn (AccessUserAdministrationAuditProjection $item): array => $item->toSafeArray(),
                $this->items,
            ),
        ];
    }
}
