<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Metadata-only aggregate summary of controlled Accessing admin actions.
 */
final readonly class AccessingAccountAdministrationAuditSummary
{
    /**
     * @param array<string, int> $countByStatus
     * @param array<string, int> $countByAction
     */
    public function __construct(
        private int $total,
        private array $countByStatus = [],
        private array $countByAction = [],
        private ?\DateTimeImmutable $latestAt = null,
    ) {
    }

    public function total(): int
    {
        return $this->total;
    }

    /** @return array<string, int> */
    public function countByStatus(): array
    {
        return $this->countByStatus;
    }

    /** @return array<string, int> */
    public function countByAction(): array
    {
        return $this->countByAction;
    }

    public function latestAt(): ?\DateTimeImmutable
    {
        return $this->latestAt;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'total' => $this->total,
            'count_by_status' => $this->countByStatus,
            'count_by_action' => $this->countByAction,
            'latest_at' => $this->latestAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
