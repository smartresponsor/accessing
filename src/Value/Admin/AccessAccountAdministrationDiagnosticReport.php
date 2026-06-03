<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Safe diagnostic report for Accessing account-administration integration.
 */
final readonly class AccessAccountAdministrationDiagnosticReport
{
    /**
     * @param list<AccessAccountAdministrationDiagnosticIssue> $issues
     * @param list<string>                                     $guards
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $issues,
        private array $guards = [],
    ) {
    }

    /** @return list<AccessAccountAdministrationDiagnosticIssue> */
    public function issues(): array
    {
        return $this->issues;
    }

    /** @return array<string, int> */
    private function countBy(string $method): array
    {
        $counts = [];
        foreach ($this->issues as $issue) {
            $key = (string) $issue->{$method}();
            $counts[$key] = ($counts[$key] ?? 0) + 1;
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
                'totalIssues' => count($this->issues),
                'blockingIssues' => count(array_filter(
                    $this->issues,
                    static fn (AccessAccountAdministrationDiagnosticIssue $issue): bool => $issue->blocking(),
                )),
                'bySeverity' => $this->countBy('severity'),
                'byStatus' => $this->countBy('status'),
            ],
            'issues' => array_map(
                static fn (AccessAccountAdministrationDiagnosticIssue $issue): array => $issue->toSafeArray(),
                $this->issues,
            ),
            'guards' => $this->guards,
        ];
    }
}
