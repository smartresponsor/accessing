<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationAuditProjectionProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationReadinessReportProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationActionDescriptor;
use App\Accessing\Value\Admin\AccessingAccountAdministrationReadinessReport;

/**
 * Builds a safe readiness summary for Accessing account administration integration.
 */
final readonly class AccessingAccountAdministrationReadinessReportProvider implements AccessingAccountAdministrationReadinessReportProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationActionCatalogInterface $actionCatalog,
        private AccessingAccountAdministrationAuditProjectionProviderInterface $auditProjectionProvider,
        private AccessingAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function report(int $auditLimit = 100): AccessingAccountAdministrationReadinessReport
    {
        return new AccessingAccountAdministrationReadinessReport(
            new \DateTimeImmutable(),
            array_map(
                static fn (AccessingAccountAdministrationActionDescriptor $descriptor): array => [
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
