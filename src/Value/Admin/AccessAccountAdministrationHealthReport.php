<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe health report for Accessing account-administration integration.
 */
final readonly class AccessAccountAdministrationHealthReport
{
    /**
     * @param list<AccessAccountAdministrationHealthDescriptor> $checks
     * @param list<string>                                      $guards
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $checks,
        private array $guards = [],
    ) {
    }

    /** @return list<AccessAccountAdministrationHealthDescriptor> */
    public function checks(): array
    {
        return $this->checks;
    }

    /** @return array<string, int> */
    private function countByStatus(): array
    {
        $counts = [];
        foreach ($this->checks as $check) {
            $status = $check->status();
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /** @return array<string, int> */
    private function countBySeverity(): array
    {
        $counts = [];
        foreach ($this->checks as $check) {
            $severity = $check->severity();
            $counts[$severity] = ($counts[$severity] ?? 0) + 1;
        }

        ksort($counts);

        return $counts;
    }

    /** @return array<string, mixed> */
    public function toSafeArray(): array
    {
        return [
            'generatedAt' => $this->generatedAt->format(\DateTimeInterface::ATOM),
            'summary' => [
                'totalChecks' => count($this->checks),
                'blockingChecks' => count(array_filter(
                    $this->checks,
                    static fn (AccessAccountAdministrationHealthDescriptor $check): bool => $check->blocking(),
                )),
                'byStatus' => $this->countByStatus(),
                'bySeverity' => $this->countBySeverity(),
            ],
            'checks' => array_map(
                static fn (AccessAccountAdministrationHealthDescriptor $check): array => $check->toSafeArray(),
                $this->checks,
            ),
            'guards' => $this->guards,
        ];
    }
}
