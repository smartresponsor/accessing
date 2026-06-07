<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationCapabilityMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationCapabilityDescriptor;
use App\Accessing\Value\Admin\AccessUserAdministrationCapabilityMatrix;

/**
 * Builds a safe capability matrix for Administering/host diagnostics.
 */
final readonly class AccessUserAdministrationCapabilityMatrixProvider implements AccessUserAdministrationCapabilityMatrixProviderInterface
{
    public function __construct(
        private AccessUserAdministrationActionCatalogInterface $actionCatalog,
        private AccessUserAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function matrix(): AccessUserAdministrationCapabilityMatrix
    {
        $executionMode = $this->executionReadinessProvider->report()->executionMode();
        $executable = 'component_owned' === $executionMode || 'doctrine_backed' === $executionMode;

        $capabilities = [];
        foreach ($this->actionCatalog->descriptors() as $descriptor) {
            $capabilities[] = new AccessUserAdministrationCapabilityDescriptor(
                $descriptor->key(),
                $descriptor->label(),
                'user_action',
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

        $capabilities[] = new AccessUserAdministrationCapabilityDescriptor(
            'accessing.user.projection.view',
            'View safe user projections',
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

        return new AccessUserAdministrationCapabilityMatrix(
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
