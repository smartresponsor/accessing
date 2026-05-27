<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationCapabilityMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationContractMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationHealthReportProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationHealthDescriptor;
use App\Accessing\Value\Admin\AccessingAccountAdministrationHealthReport;

/**
 * Builds a safe health report for the Accessing administration surface.
 */
final readonly class AccessingAccountAdministrationHealthReportProvider implements AccessingAccountAdministrationHealthReportProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
        private AccessingAccountAdministrationCapabilityMatrixProviderInterface $capabilityMatrixProvider,
        private AccessingAccountAdministrationContractMatrixProviderInterface $contractMatrixProvider,
    ) {
    }

    public function report(): AccessingAccountAdministrationHealthReport
    {
        $execution = $this->executionReadinessProvider->report()->toSafeArray();
        $executionMode = (string) ($execution['executionMode'] ?? 'bootstrap');
        $executionReady = in_array($executionMode, ['component_owned', 'doctrine_backed'], true);

        $capabilitySummary = $this->capabilityMatrixProvider->matrix()->toSafeArray()['summary'] ?? [];
        $contractSummary = $this->contractMatrixProvider->matrix()->toSafeArray()['summary'] ?? [];

        return new AccessingAccountAdministrationHealthReport(
            new \DateTimeImmutable(),
            [
                new AccessingAccountAdministrationHealthDescriptor(
                    'accessing.identity_owner',
                    'Accessing remains authentication/session owner',
                    'ownership',
                    'healthy',
                    'info',
                    false,
                    [
                        'owner' => 'Accessing',
                        'administeringRole' => 'consumer_or_requester',
                    ],
                ),
                new AccessingAccountAdministrationHealthDescriptor(
                    'accessing.execution_mode',
                    'Controlled account action execution mode',
                    'execution',
                    $executionReady ? 'healthy' : 'degraded',
                    $executionReady ? 'info' : 'warning',
                    !$executionReady,
                    [
                        'executionMode' => $executionMode,
                        'readyCapabilities' => $execution['readyCapabilities'] ?? [],
                        'pendingCapabilities' => $execution['pendingCapabilities'] ?? [],
                    ],
                ),
                new AccessingAccountAdministrationHealthDescriptor(
                    'accessing.capability_matrix',
                    'Safe account administration capability matrix',
                    'capability',
                    'healthy',
                    'info',
                    false,
                    is_array($capabilitySummary) ? $capabilitySummary : [],
                ),
                new AccessingAccountAdministrationHealthDescriptor(
                    'accessing.contract_matrix',
                    'Safe account administration contract matrix',
                    'contract',
                    'healthy',
                    'info',
                    false,
                    is_array($contractSummary) ? $contractSummary : [],
                ),
                new AccessingAccountAdministrationHealthDescriptor(
                    'accessing.raw_security_boundary',
                    'Forbidden raw security internals boundary',
                    'security_boundary',
                    'protected',
                    'info',
                    false,
                    [
                        'forbiddenPayloads' => ['password hashes', 'TOTP secrets', 'recovery codes', 'reset tokens', 'raw session payloads', 'verification internals'],
                    ],
                ),
            ],
            [
                'Accessing health descriptors never expose passwords, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
                'A degraded execution mode is acceptable during bootstrap, but real account mutations remain blocked until component-owned execution is implemented.',
            ],
        );
    }
}
