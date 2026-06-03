<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationContractMatrixProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationContractDescriptor;
use App\Accessing\Value\Admin\AccessingAccountAdministrationContractMatrix;

/**
 * Builds a safe contract matrix for Administering integration diagnostics.
 */
final readonly class AccessingAccountAdministrationContractMatrixProvider implements AccessingAccountAdministrationContractMatrixProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function matrix(): AccessingAccountAdministrationContractMatrix
    {
        $readiness = $this->executionReadinessProvider->report();
        $runtimeMode = $readiness->executionMode();
        $executionReady = 'component_owned' === $runtimeMode || 'doctrine_backed' === $runtimeMode;

        return new AccessingAccountAdministrationContractMatrix(
            new \DateTimeImmutable(),
            [
                $this->contract('accessing.current_account_context', 'Current account context provider', 'identity_projection', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.account_projection_provider', 'Safe account projection provider', 'account_projection', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.account_action_catalog', 'Controlled account action catalog', 'account_action', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.account_action_validator', 'Controlled account action request validator', 'account_action', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.account_action_bridge', 'Controlled account action bridge', 'account_action', $executionReady ? 'ready' : 'bootstrap', true, true, $runtimeMode),
                $this->contract('accessing.account_action_audit_projection', 'Account action audit projection provider', 'audit_projection', 'ready', true, false, $runtimeMode),
                $this->contract('accessing.account_action_execution', 'Component-owned account action execution', 'mutation_execution', $executionReady ? 'ready' : 'blocked', true, true, $runtimeMode),
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
    ): AccessingAccountAdministrationContractDescriptor {
        return new AccessingAccountAdministrationContractDescriptor(
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
