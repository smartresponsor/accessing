<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationCapabilityMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationContractMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationHealthReportProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationHealthDescriptor;
use App\Accessing\Value\Admin\AccessUserAdministrationHealthReport;

/**
 * Builds a safe health report for the Accessing administration surface.
 */
final readonly class AccessUserAdministrationHealthReportProvider implements AccessUserAdministrationHealthReportProviderInterface
{
    public function __construct(
        private AccessUserAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
        private AccessUserAdministrationCapabilityMatrixProviderInterface $capabilityMatrixProvider,
        private AccessUserAdministrationContractMatrixProviderInterface $contractMatrixProvider,
    ) {
    }

    public function report(): AccessUserAdministrationHealthReport
    {
        $execution = $this->executionReadinessProvider->report();
        $executionMode = $execution->executionMode();
        $executionReady = in_array($executionMode, ['component_owned', 'doctrine_backed'], true);

        $capabilitySummary = self::arrayValue($this->capabilityMatrixProvider->matrix()->toSafeArray()['summary'] ?? []);
        $contractSummary = self::arrayValue($this->contractMatrixProvider->matrix()->toSafeArray()['summary'] ?? []);

        return new AccessUserAdministrationHealthReport(
            new \DateTimeImmutable(),
            [
                new AccessUserAdministrationHealthDescriptor(
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
                new AccessUserAdministrationHealthDescriptor(
                    'accessing.execution_mode',
                    'Controlled user action execution mode',
                    'execution',
                    $executionReady ? 'healthy' : 'degraded',
                    $executionReady ? 'info' : 'warning',
                    !$executionReady,
                    [
                        'executionMode' => $executionMode,
                        'readyCapabilities' => $execution->readyCapabilities(),
                        'pendingCapabilities' => $execution->pendingCapabilities(),
                    ],
                ),
                new AccessUserAdministrationHealthDescriptor(
                    'accessing.capability_matrix',
                    'Safe user administration capability matrix',
                    'capability',
                    'healthy',
                    'info',
                    false,
                    $capabilitySummary,
                ),
                new AccessUserAdministrationHealthDescriptor(
                    'accessing.contract_matrix',
                    'Safe user administration contract matrix',
                    'contract',
                    'healthy',
                    'info',
                    false,
                    $contractSummary,
                ),
                new AccessUserAdministrationHealthDescriptor(
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
                'A degraded execution mode is acceptable during bootstrap, but real user mutations remain blocked until component-owned execution is implemented.',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayValue(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $normalized[$key] = $item;
            }
        }

        return $normalized;
    }
}
