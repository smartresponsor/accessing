<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionPlanProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationWorkPlanProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationExecutionPlan;

/**
 * Converts Accessing work/readiness metadata into safe execution-planning steps.
 */
final readonly class AccessingAccountAdministrationExecutionPlanProvider implements AccessingAccountAdministrationExecutionPlanProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationWorkPlanProviderInterface $workPlanProvider,
        private AccessingAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function plan(): AccessingAccountAdministrationExecutionPlan
    {
        $readiness = $this->executionReadinessProvider->report()->toSafeArray();
        $steps = [];

        foreach ($this->workPlanProvider->plan()->items() as $item) {
            $blocksMutation = (bool) ($item['blocksMutation'] ?? false);
            $steps[] = [
                'key' => 'execute.'.(string) $item['key'],
                'title' => (string) $item['title'],
                'component' => 'Accessing',
                'stage' => (string) $item['stage'],
                'executionType' => $blocksMutation ? 'manual_implementation' : 'policy_guard',
                'blocked' => $blocksMutation,
                'requiresReview' => true,
                'safeToAutomate' => false,
                'sourceWorkItem' => (string) $item['key'],
                'context' => [
                    'currentExecutionMode' => $readiness['executionMode'] ?? 'unknown',
                    'actionType' => $item['actionType'] ?? 'unknown',
                    'dependsOn' => $item['dependsOn'] ?? [],
                    'note' => $blocksMutation
                        ? 'Accessing must implement this inside its owned account/security boundary before Administering can execute it.'
                        : 'This is a governance guard and may be displayed by Administering without mutating account internals.',
                ],
            ];
        }

        return new AccessingAccountAdministrationExecutionPlan(
            new \DateTimeImmutable(),
            $steps,
            [
                'Accessing owns authentication, sessions, account state, password and second-factor internals.',
                'Administering may request only catalogued controlled account actions.',
                'No execution step may expose password hashes, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
            ],
        );
    }
}
