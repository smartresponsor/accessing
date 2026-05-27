<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationRemediationPlanProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationWorkPlanProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationActionDescriptor;
use App\Accessing\Value\Admin\AccessingAccountAdministrationWorkPlan;

/**
 * Builds safe, actionable Accessing work items without exposing account security internals.
 */
final readonly class AccessingAccountAdministrationWorkPlanProvider implements AccessingAccountAdministrationWorkPlanProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationRemediationPlanProviderInterface $remediationPlanProvider,
        private AccessingAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
        private AccessingAccountAdministrationActionCatalogInterface $actionCatalog,
    ) {
    }

    public function plan(): AccessingAccountAdministrationWorkPlan
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
            static fn (AccessingAccountAdministrationActionDescriptor $descriptor): string => $descriptor->key(),
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

        return new AccessingAccountAdministrationWorkPlan(new \DateTimeImmutable(), $items);
    }
}
