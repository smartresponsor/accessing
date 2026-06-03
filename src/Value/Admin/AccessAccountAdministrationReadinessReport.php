<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe readiness report for Accessing administration surfaces consumed by Administering.
 */
final readonly class AccessAccountAdministrationReadinessReport
{
    /**
     * @param list<array<string, mixed>> $actions
     * @param array<string, mixed>       $auditSummary
     * @param array<string, mixed>       $executionReadiness
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $actions,
        private array $auditSummary,
        private array $executionReadiness = [],
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function actions(): array
    {
        return $this->actions;
    }

    /** @return array<string, mixed> */
    public function auditSummary(): array
    {
        return $this->auditSummary;
    }

    /** @return array<string, mixed> */
    public function executionReadiness(): array
    {
        return $this->executionReadiness;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
            'actions' => $this->actions,
            'auditSummary' => $this->auditSummary,
            'executionReadiness' => $this->executionReadiness,
        ];
    }
}
