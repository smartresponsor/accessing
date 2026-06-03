<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationCapabilityMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationContractMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationHealthReportProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationHealthDescriptor;
use App\Accessing\Value\Admin\AccessAccountAdministrationHealthReport;

/**
 * Builds a safe health report for the Accessing administration surface.
 */
final readonly class AccessAccountAdministrationHealthReportProvider implements AccessAccountAdministrationHealthReportProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
        private AccessAccountAdministrationCapabilityMatrixProviderInterface $capabilityMatrixProvider,
        private AccessAccountAdministrationContractMatrixProviderInterface $contractMatrixProvider,
    ) {
    }

    public function report(): AccessAccountAdministrationHealthReport
    {
        $execution = $this->executionReadinessProvider->report()->toSafeArray();
        $executionMode = (string) ($execution['executionMode'] ?? 'bootstrap');
        $executionReady = in_array($executionMode, ['component_owned', 'doctrine_backed'], true);

        $capabilitySummary = $this->capabilityMatrixProvider->matrix()->toSafeArray()['summary'] ?? [];
        $contractSummary = $this->contractMatrixProvider->matrix()->toSafeArray()['summary'] ?? [];

        return new AccessAccountAdministrationHealthReport(
            new \DateTimeImmutable(),
            [
                new AccessAccountAdministrationHealthDescriptor(
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
                new AccessAccountAdministrationHealthDescriptor(
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
                new AccessAccountAdministrationHealthDescriptor(
                    'accessing.capability_matrix',
                    'Safe account administration capability matrix',
                    'capability',
                    'healthy',
                    'info',
                    false,
                    is_array($capabilitySummary) ? $capabilitySummary : [],
                ),
                new AccessAccountAdministrationHealthDescriptor(
                    'accessing.contract_matrix',
                    'Safe account administration contract matrix',
                    'contract',
                    'healthy',
                    'info',
                    false,
                    is_array($contractSummary) ? $contractSummary : [],
                ),
                new AccessAccountAdministrationHealthDescriptor(
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
