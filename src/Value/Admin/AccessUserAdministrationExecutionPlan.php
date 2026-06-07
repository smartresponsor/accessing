<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Metadata-only execution plan for Accessing user administration hardening.
 */
final readonly class AccessUserAdministrationExecutionPlan
{
    /**
     * @param list<array<string, mixed>> $steps
     * @param list<string>               $guards
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $steps,
        private array $guards = [],
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function steps(): array
    {
        return $this->steps;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
            'summary' => [
                'totalSteps' => count($this->steps),
                'blockedSteps' => count(array_filter(
                    $this->steps,
                    static fn (array $step): bool => true === ($step['blocked'] ?? false),
                )),
            ],
            'steps' => $this->steps,
            'guards' => $this->guards,
        ];
    }
}
