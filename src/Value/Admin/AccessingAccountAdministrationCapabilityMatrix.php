<?php

declare(strict_types=1);

namespace App\Accessing\Value\Admin;

/**
 * Metadata-only capability matrix for Accessing account administration.
 */
final readonly class AccessingAccountAdministrationCapabilityMatrix
{
    /**
     * @param list<AccessingAccountAdministrationCapabilityDescriptor> $capabilities
     * @param list<string>                                             $guards
     */
    public function __construct(
        private \DateTimeImmutable $generatedAt,
        private array $capabilities,
        private array $guards = [],
    ) {
    }

    /** @return list<AccessingAccountAdministrationCapabilityDescriptor> */
    public function capabilities(): array
    {
        return $this->capabilities;
    }

    /** @return array<string, int> */
    private function countByStatus(): array
    {
        $counts = [];
        foreach ($this->capabilities as $capability) {
            $status = $capability->status();
            $counts[$status] = ($counts[$status] ?? 0) + 1;
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
                'totalCapabilities' => count($this->capabilities),
                'executableCapabilities' => count(array_filter(
                    $this->capabilities,
                    static fn (AccessingAccountAdministrationCapabilityDescriptor $capability): bool => $capability->executable(),
                )),
                'byStatus' => $this->countByStatus(),
            ],
            'capabilities' => array_map(
                static fn (AccessingAccountAdministrationCapabilityDescriptor $capability): array => $capability->toSafeArray(),
                $this->capabilities,
            ),
            'guards' => $this->guards,
        ];
    }
}
