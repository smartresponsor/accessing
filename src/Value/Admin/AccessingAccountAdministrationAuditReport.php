<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Metadata-only report for controlled account-action audit.
 *
 * The report must never include password hashes, TOTP secrets, recovery codes,
 * reset tokens, raw session payloads, or verification internals.
 */
final readonly class AccessingAccountAdministrationAuditReport
{
    /** @param list<AccessingAccountAdministrationAuditProjection> $items */
    public function __construct(
        private AccessingAccountAdministrationAuditFilter $filter,
        private AccessingAccountAdministrationAuditSummary $summary,
        private array $items,
    ) {
    }

    public function filter(): AccessingAccountAdministrationAuditFilter
    {
        return $this->filter;
    }

    public function summary(): AccessingAccountAdministrationAuditSummary
    {
        return $this->summary;
    }

    /** @return list<AccessingAccountAdministrationAuditProjection> */
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
                static fn (AccessingAccountAdministrationAuditProjection $item): array => $item->toSafeArray(),
                $this->items,
            ),
        ];
    }
}
