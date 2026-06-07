<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionPlanProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationWorkPlanProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationExecutionPlan;

/**
 * Converts Accessing work/readiness metadata into safe execution-planning steps.
 */
final readonly class AccessUserAdministrationExecutionPlanProvider implements AccessUserAdministrationExecutionPlanProviderInterface
{
    public function __construct(
        private AccessUserAdministrationWorkPlanProviderInterface $workPlanProvider,
        private AccessUserAdministrationExecutionReadinessProviderInterface $executionReadinessProvider,
    ) {
    }

    public function plan(): AccessUserAdministrationExecutionPlan
    {
        $readiness = $this->executionReadinessProvider->report();
        $steps = [];

        foreach ($this->workPlanProvider->plan()->items() as $item) {
            $blocksMutation = (bool) ($item['blocksMutation'] ?? false);
            $steps[] = [
                'key' => 'execute.'.self::stringValue($item['key'] ?? null, 'unknown'),
                'title' => self::stringValue($item['title'] ?? null, 'Untitled'),
                'component' => 'Accessing',
                'stage' => self::stringValue($item['stage'] ?? null, 'unknown'),
                'executionType' => $blocksMutation ? 'manual_implementation' : 'policy_guard',
                'blocked' => $blocksMutation,
                'requiresReview' => true,
                'safeToAutomate' => false,
                'sourceWorkItem' => self::stringValue($item['key'] ?? null, 'unknown'),
                'context' => [
                    'currentExecutionMode' => $readiness->executionMode(),
                    'actionType' => self::stringValue($item['actionType'] ?? null, 'unknown'),
                    'dependsOn' => self::listValue($item['dependsOn'] ?? null),
                    'note' => $blocksMutation
                        ? 'Accessing must implement this inside its owned user/security boundary before Administering can execute it.'
                        : 'This is a governance guard and may be displayed by Administering without mutating user internals.',
                ],
            ];
        }

        return new AccessUserAdministrationExecutionPlan(
            new \DateTimeImmutable(),
            $steps,
            [
                'Accessing owns authentication, sessions, user state, password and second-factor internals.',
                'Administering may request only catalogued controlled user actions.',
                'No execution step may expose password hashes, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
            ],
        );
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

    /** @return list<mixed> */
    private static function listValue(mixed $value): array
    {
        return is_array($value) ? array_values($value) : [];
    }
}
