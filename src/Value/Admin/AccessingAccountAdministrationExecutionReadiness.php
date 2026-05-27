<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe readiness view for controlled Accessing account action execution.
 */
final readonly class AccessingAccountAdministrationExecutionReadiness
{
    /**
     * @param list<string> $readyCapabilities
     * @param list<string> $pendingCapabilities
     */
    public function __construct(
        private string $executionMode,
        private bool $persistentAuditEnabled,
        private array $readyCapabilities,
        private array $pendingCapabilities,
    ) {
    }

    public function executionMode(): string
    {
        return $this->executionMode;
    }

    public function persistentAuditEnabled(): bool
    {
        return $this->persistentAuditEnabled;
    }

    /** @return list<string> */
    public function readyCapabilities(): array
    {
        return $this->readyCapabilities;
    }

    /** @return list<string> */
    public function pendingCapabilities(): array
    {
        return $this->pendingCapabilities;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'executionMode' => $this->executionMode,
            'persistentAuditEnabled' => $this->persistentAuditEnabled,
            'readyCapabilities' => $this->readyCapabilities,
            'pendingCapabilities' => $this->pendingCapabilities,
        ];
    }
}
