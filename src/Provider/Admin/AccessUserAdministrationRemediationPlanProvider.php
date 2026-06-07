<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationRemediationPlanProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationRemediationPlan;

/**
 * Builds safe next-step guidance for Accessing controlled user administration.
 */
final readonly class AccessUserAdministrationRemediationPlanProvider implements AccessUserAdministrationRemediationPlanProviderInterface
{
    public function __construct(
        private AccessUserAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function plan(): AccessUserAdministrationRemediationPlan
    {
        $execution = $this->executionReadinessProvider->report()->toSafeArray();
        $items = [];

        if (($execution['executionMode'] ?? 'bootstrap') !== 'doctrine') {
            $items[] = [
                'key' => 'accessing.execution.bootstrap',
                'severity' => 'warning',
                'title' => 'Accessing user actions still use bootstrap execution mode.',
                'recommendation' => 'Replace bootstrap user-action execution with Accessing-owned Doctrine-backed controlled mutations before enabling irreversible user actions from Administering.',
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
                'title' => 'Accessing user-action audit is not marked as persistent.',
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
                'title' => 'Accessing controlled user administration is ready for Administering orchestration.',
                'recommendation' => 'Continue keeping login/session/password/2FA ownership inside Accessing and expose only controlled actions to Administering.',
                'blocksMutations' => false,
                'context' => [],
            ];
        }

        return new AccessUserAdministrationRemediationPlan(new \DateTimeImmutable(), $items);
    }
}
