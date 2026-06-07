<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationAuditProjectionProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationReadinessReportProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationActionDescriptor;
use App\Accessing\Value\Admin\AccessUserAdministrationReadinessReport;

/**
 * Builds a safe readiness summary for Accessing user administration integration.
 */
final readonly class AccessUserAdministrationReadinessReportProvider implements AccessUserAdministrationReadinessReportProviderInterface
{
    public function __construct(
        private AccessUserAdministrationActionCatalogInterface $actionCatalog,
        private AccessUserAdministrationAuditProjectionProviderInterface $auditProjectionProvider,
        private AccessUserAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function report(int $auditLimit = 100): AccessUserAdministrationReadinessReport
    {
        return new AccessUserAdministrationReadinessReport(
            new \DateTimeImmutable(),
            array_map(
                static fn (AccessUserAdministrationActionDescriptor $descriptor): array => [
                    'key' => $descriptor->key(),
                    'label' => $descriptor->label(),
                    'riskLevel' => $descriptor->riskLevel(),
                    'requiresReason' => $descriptor->requiresReason(),
                ],
                $this->actionCatalog->descriptors(),
            ),
            $this->auditProjectionProvider->summary($auditLimit)->toSafeArray(),
            $this->executionReadinessProvider->report()->toSafeArray(),
        );
    }
}
