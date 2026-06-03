<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationCapabilityMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationCapabilityDescriptor;
use App\Accessing\Value\Admin\AccessAccountAdministrationCapabilityMatrix;

/**
 * Builds a safe capability matrix for Administering/host diagnostics.
 */
final readonly class AccessAccountAdministrationCapabilityMatrixProvider implements AccessAccountAdministrationCapabilityMatrixProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationActionCatalogInterface $actionCatalog,
        private AccessAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function matrix(): AccessAccountAdministrationCapabilityMatrix
    {
        $readiness = $this->executionReadinessProvider->report()->toSafeArray();
        $executionMode = (string) ($readiness['executionMode'] ?? 'bootstrap');
        $executable = 'component_owned' === $executionMode || 'doctrine_backed' === $executionMode;

        $capabilities = [];
        foreach ($this->actionCatalog->descriptors() as $descriptor) {
            $capabilities[] = new AccessAccountAdministrationCapabilityDescriptor(
                $descriptor->key(),
                $descriptor->label(),
                'account_action',
                $executable ? 'ready' : 'blocked',
                'low' !== $descriptor->riskLevel(),
                $executable,
                $descriptor->requiresReason(),
                [
                    'riskLevel' => $descriptor->riskLevel(),
                    'executionMode' => $executionMode,
                    'owner' => 'Accessing',
                    'administeringRole' => 'requester_or_visualizer',
                ],
            );
        }

        $capabilities[] = new AccessAccountAdministrationCapabilityDescriptor(
            'accessing.account.projection.view',
            'View safe account projections',
            'projection',
            'ready',
            false,
            true,
            false,
            [
                'owner' => 'Accessing',
                'exposes' => ['subject id', 'identifier', 'active state', 'verification state', 'bootstrap roles'],
            ],
        );

        return new AccessAccountAdministrationCapabilityMatrix(
            new \DateTimeImmutable(),
            $capabilities,
            [
                'Accessing owns authentication, sessions, password, verification and second-factor internals.',
                'Administering may display safe projections and request only catalogued controlled actions.',
                'No capability descriptor may expose password hashes, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
            ],
        );
    }
}
