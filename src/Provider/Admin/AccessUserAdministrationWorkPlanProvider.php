<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationRemediationPlanProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationWorkPlanProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationActionDescriptor;
use App\Accessing\Value\Admin\AccessUserAdministrationWorkPlan;

/**
 * Builds safe, actionable Accessing work items without exposing user security internals.
 */
final readonly class AccessUserAdministrationWorkPlanProvider implements AccessUserAdministrationWorkPlanProviderInterface
{
    public function __construct(
        private AccessUserAdministrationRemediationPlanProviderInterface $remediationPlanProvider,
        private AccessUserAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
        private AccessUserAdministrationActionCatalogInterface $actionCatalog,
    ) {
    }

    public function plan(): AccessUserAdministrationWorkPlan
    {
        $execution = $this->executionReadinessProvider->report();
        $items = [];

        foreach ($this->remediationPlanProvider->plan()->items() as $remediationItem) {
            $items[] = [
                'key' => 'accessing.remediation.'.self::stringValue($remediationItem['key'] ?? null, 'unknown'),
                'title' => self::stringValue($remediationItem['title'] ?? null, 'Untitled'),
                'stage' => 'hardening',
                'priority' => true === ($remediationItem['blocksMutations'] ?? false) ? 'high' : 'normal',
                'actionType' => 'remediation',
                'blocksMutation' => (bool) ($remediationItem['blocksMutations'] ?? false),
                'dependsOn' => [],
                'context' => [
                    'recommendation' => self::nullableString($remediationItem['recommendation'] ?? null),
                    'sourceSeverity' => self::stringValue($remediationItem['severity'] ?? null, 'info'),
                    'sourceContext' => self::arrayValue($remediationItem['context'] ?? null),
                ],
            ];
        }

        if ('doctrine' !== $execution->executionMode()) {
            $items[] = [
                'key' => 'accessing.execution.promote_to_doctrine',
                'title' => 'Promote Accessing controlled user actions to Accessing-owned Doctrine execution.',
                'stage' => 'persistence',
                'priority' => 'high',
                'actionType' => 'implementation',
                'blocksMutation' => true,
                'dependsOn' => ['accessing.audit.persistence'],
                'context' => [
                    'currentExecutionMode' => $execution->executionMode(),
                    'pendingCapabilities' => $execution->pendingCapabilities(),
                ],
            ];
        }

        $actions = array_map(
            static fn (AccessUserAdministrationActionDescriptor $descriptor): string => $descriptor->key(),
            $this->actionCatalog->descriptors(),
        );

        $items[] = [
            'key' => 'accessing.action_catalog.keep_safe',
            'title' => 'Keep Accessing user-action catalog limited to controlled operations.',
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

        return new AccessUserAdministrationWorkPlan(new \DateTimeImmutable(), $items);
    }

    private static function stringValue(mixed $value, string $default): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value) || null === $value) {
            return (string) ($value ?? $default);
        }

        return $default;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }

        return self::stringValue($value, '');
    }

    /** @return array<string, mixed> */
    private static function arrayValue(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $key => $item) {
            if (is_string($key)) {
                $normalized[$key] = $item;
            }
        }

        return $normalized;
    }
}
