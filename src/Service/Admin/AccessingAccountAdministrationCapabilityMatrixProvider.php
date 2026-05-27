<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationCapabilityMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationCapabilityDescriptor;
use App\Accessing\Value\Admin\AccessingAccountAdministrationCapabilityMatrix;

/**
 * Builds a safe capability matrix for Administering/host diagnostics.
 */
final readonly class AccessingAccountAdministrationCapabilityMatrixProvider implements AccessingAccountAdministrationCapabilityMatrixProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationActionCatalogInterface $actionCatalog,
        private AccessingAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function matrix(): AccessingAccountAdministrationCapabilityMatrix
    {
        $readiness = $this->executionReadinessProvider->report()->toSafeArray();
        $executionMode = (string) ($readiness['executionMode'] ?? 'bootstrap');
        $executable = 'component_owned' === $executionMode || 'doctrine_backed' === $executionMode;

        $capabilities = [];
        foreach ($this->actionCatalog->descriptors() as $descriptor) {
            $capabilities[] = new AccessingAccountAdministrationCapabilityDescriptor(
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

        $capabilities[] = new AccessingAccountAdministrationCapabilityDescriptor(
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

        return new AccessingAccountAdministrationCapabilityMatrix(
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
