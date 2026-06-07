<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationContractMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationContractDescriptor;
use App\Accessing\Value\Admin\AccessUserAdministrationContractMatrix;

/**
 * Builds a safe contract matrix for Administering integration diagnostics.
 */
final readonly class AccessUserAdministrationContractMatrixProvider implements AccessUserAdministrationContractMatrixProviderInterface
{
    public function __construct(
        private AccessUserAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function matrix(): AccessUserAdministrationContractMatrix
    {
        $readiness = $this->executionReadinessProvider->report();
        $runtimeMode = $readiness->executionMode();
        $executionReady = 'component_owned' === $runtimeMode || 'doctrine_backed' === $runtimeMode;

        return new AccessUserAdministrationContractMatrix(
            new \DateTimeImmutable(),
            [
                $this->contract('accessing.current_user_context', 'Current user context provider', 'identity_projection', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.user_projection_provider', 'Safe user projection provider', 'user_projection', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.user_action_catalog', 'Controlled user action catalog', 'user_action', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.user_action_validator', 'Controlled user action request validator', 'user_action', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.user_action_bridge', 'Controlled user action bridge', 'user_action', $executionReady ? 'ready' : 'bootstrap', true, true, $runtimeMode),
                $this->contract('accessing.user_action_audit_projection', 'User action audit projection provider', 'audit_projection', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.user_action_execution', 'Component-owned user action execution', 'mutation_execution', $executionReady ? 'ready' : 'blocked', true, true, $runtimeMode),
                $this->contract('accessing.security_internals_raw_edit', 'Raw security internals editing boundary', 'forbidden_boundary', 'forbidden', true, true, $runtimeMode, [
                    'forbiddenFields' => ['password hash', 'TOTP secret', 'recovery codes', 'reset tokens', 'raw session payload'],
                ]),
            ],
            [
                'Accessing owns authentication, sessions, passwords, verification and second-factor internals.',
                'Administering may consume safe contracts but must not duplicate login/session ownership.',
                'Sensitive contract descriptors describe risk only; they do not expose secret or raw security values.',
            ],
        );
    }

    /** @param array<string, mixed> $context */
    private function contract(
        string $key,
        string $label,
        string $category,
        string $status,
        bool $required,
        bool $sensitive,
        string $runtimeMode,
        array $context = [],
    ): AccessUserAdministrationContractDescriptor {
        return new AccessUserAdministrationContractDescriptor(
            $key,
            $label,
            $category,
            $status,
            'Accessing',
            'Administering',
            $required,
            $sensitive,
            $runtimeMode,
            $context + [
                'owner' => 'Accessing',
                'administeringRole' => 'consumer_or_requester',
            ],
        );
    }
}
