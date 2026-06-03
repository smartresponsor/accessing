<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationAuditProjectionProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationReadinessReportProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationActionDescriptor;
use App\Accessing\Value\Admin\AccessAccountAdministrationReadinessReport;

/**
 * Builds a safe readiness summary for Accessing account administration integration.
 */
final readonly class AccessAccountAdministrationReadinessReportProvider implements AccessAccountAdministrationReadinessReportProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationActionCatalogInterface $actionCatalog,
        private AccessAccountAdministrationAuditProjectionProviderInterface $auditProjectionProvider,
        private AccessAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function report(int $auditLimit = 100): AccessAccountAdministrationReadinessReport
    {
        return new AccessAccountAdministrationReadinessReport(
            new \DateTimeImmutable(),
            array_map(
                static fn (AccessAccountAdministrationActionDescriptor $descriptor): array => [
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
