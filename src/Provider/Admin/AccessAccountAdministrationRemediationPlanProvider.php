<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationRemediationPlanProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationRemediationPlan;

/**
 * Builds safe next-step guidance for Accessing controlled account administration.
 */
final readonly class AccessAccountAdministrationRemediationPlanProvider implements AccessAccountAdministrationRemediationPlanProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function plan(): AccessAccountAdministrationRemediationPlan
    {
        $execution = $this->executionReadinessProvider->report()->toSafeArray();
        $items = [];

        if (($execution['executionMode'] ?? 'bootstrap') !== 'doctrine') {
            $items[] = [
                'key' => 'accessing.execution.bootstrap',
                'severity' => 'warning',
                'title' => 'Accessing account actions still use bootstrap execution mode.',
                'recommendation' => 'Replace bootstrap account-action execution with Accessing-owned Doctrine-backed controlled mutations before enabling irreversible account actions from Administering.',
                'blocksMutations' => true,
                'context' => [
                    'executionMode' => $execution['executionMode'] ?? 'unknown',
                    'readyCapabilities' => $execution['readyCapabilities'] ?? [],
                    'pendingCapabilities' => $execution['pendingCapabilities'] ?? [],
                ],
            ];
        }

        if (($execution['persistentAuditEnabled'] ?? false) !== true) {
            $items[] = [
                'key' => 'accessing.audit.persistence',
                'severity' => 'warning',
                'title' => 'Accessing account-action audit is not marked as persistent.',
                'recommendation' => 'Keep Administering actions in dry-run/review mode until Accessing-owned action audit persistence is confirmed.',
                'blocksMutations' => false,
                'context' => [
                    'persistentAuditEnabled' => $execution['persistentAuditEnabled'] ?? false,
                ],
            ];
        }

        if ([] === $items) {
            $items[] = [
                'key' => 'accessing.ready',
                'severity' => 'info',
                'title' => 'Accessing controlled account administration is ready for Administering orchestration.',
                'recommendation' => 'Continue keeping login/session/password/2FA ownership inside Accessing and expose only controlled actions to Administering.',
                'blocksMutations' => false,
                'context' => [],
            ];
        }

        return new AccessAccountAdministrationRemediationPlan(new \DateTimeImmutable(), $items);
    }
}
