<?php

declare(strict_types=1);

namespace App\Accessing\Policy\Lifecycle;

/**
 * Guards allowed lifecycle transitions for access.
 *
 * This policy is intentionally string-based for now so it can wrap existing
 * entity status fields without forcing a schema migration. Core status enums
 * can be introduced per component once runtime metadata validation is green.
 */
final class AccessLifecyclePolicy
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        'registered' => ['verified', 'disabled'],
        'verified' => ['active', 'disabled'],
        'active' => ['locked', 'disabled', 'deleted'],
        'locked' => ['active', 'disabled', 'deleted'],
        'disabled' => ['active', 'deleted'],
        'deleted' => [],
    ];

    /**
     * Executes the can transition operation within the canonical Accessing component workflow.
     */
    public static function canTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        if (!array_key_exists($from, self::TRANSITIONS)) {
            return false;
        }

        return in_array($to, self::TRANSITIONS[$from], true);
    }

    /**
     * Executes the assert can transition operation within the canonical Accessing component workflow.
     */
    public static function assertCanTransition(string $from, string $to): void
    {
        if (!self::canTransition($from, $to)) {
            throw new \DomainException(sprintf('Invalid Accessing lifecycle transition from "%s" to "%s".', $from, $to));
        }
    }

    /** @return list<string> */
    public static function allowedTargets(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }
}
