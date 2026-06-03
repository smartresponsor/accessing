<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationRemediationPlanProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationWorkPlanProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationActionDescriptor;
use App\Accessing\Value\Admin\AccessAccountAdministrationWorkPlan;

/**
 * Builds safe, actionable Accessing work items without exposing account security internals.
 */
final readonly class AccessAccountAdministrationWorkPlanProvider implements AccessAccountAdministrationWorkPlanProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationRemediationPlanProviderInterface $remediationPlanProvider,
        private AccessAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
        private AccessAccountAdministrationActionCatalogInterface $actionCatalog,
    ) {
    }

    public function plan(): AccessAccountAdministrationWorkPlan
    {
        $execution = $this->executionReadinessProvider->report()->toSafeArray();
        $items = [];

        foreach ($this->remediationPlanProvider->plan()->items() as $remediationItem) {
            $items[] = [
                'key' => 'accessing.remediation.'.(string) $remediationItem['key'],
                'title' => (string) $remediationItem['title'],
                'stage' => 'hardening',
                'priority' => true === ($remediationItem['blocksMutations'] ?? false) ? 'high' : 'normal',
                'actionType' => 'remediation',
                'blocksMutation' => (bool) ($remediationItem['blocksMutations'] ?? false),
                'dependsOn' => [],
                'context' => [
                    'recommendation' => $remediationItem['recommendation'] ?? null,
                    'sourceSeverity' => $remediationItem['severity'] ?? 'info',
                    'sourceContext' => $remediationItem['context'] ?? [],
                ],
            ];
        }

        if (($execution['executionMode'] ?? 'bootstrap') !== 'doctrine') {
            $items[] = [
                'key' => 'accessing.execution.promote_to_doctrine',
                'title' => 'Promote Accessing controlled account actions to Accessing-owned Doctrine execution.',
                'stage' => 'persistence',
                'priority' => 'high',
                'actionType' => 'implementation',
                'blocksMutation' => true,
                'dependsOn' => ['accessing.audit.persistence'],
                'context' => [
                    'currentExecutionMode' => $execution['executionMode'] ?? 'unknown',
                    'pendingCapabilities' => $execution['pendingCapabilities'] ?? [],
                ],
            ];
        }

        $actions = array_map(
            static fn (AccessAccountAdministrationActionDescriptor $descriptor): string => $descriptor->key(),
            $this->actionCatalog->descriptors(),
        );

        $items[] = [
            'key' => 'accessing.action_catalog.keep_safe',
            'title' => 'Keep Accessing account-action catalog limited to controlled operations.',
            'stage' => 'governance',
            'priority' => 'normal',
            'actionType' => 'policy',
            'blocksMutation' => false,
            'dependsOn' => [],
            'context' => [
                'controlledActions' => $actions,
                'forbiddenRawEdits' => [
                    'password_hash',
                    'totp_secret',
                    'recovery_codes',
                    'reset_tokens',
                    'raw_session_payload',
                ],
            ],
        ];

        return new AccessAccountAdministrationWorkPlan(new \DateTimeImmutable(), $items);
    }
}
