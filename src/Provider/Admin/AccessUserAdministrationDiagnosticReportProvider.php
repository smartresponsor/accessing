<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationDiagnosticReportProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationHealthReportProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationDiagnosticIssue;
use App\Accessing\Value\Admin\AccessUserAdministrationDiagnosticReport;

/**
 * Builds a safe issue register from Accessing-owned user-administration health checks.
 */
final readonly class AccessUserAdministrationDiagnosticReportProvider implements AccessUserAdministrationDiagnosticReportProviderInterface
{
    public function __construct(
        private AccessUserAdministrationHealthReportProviderInterface $healthReportProvider,
    ) {
    }

    public function report(): AccessUserAdministrationDiagnosticReport
    {
        $issues = [];
        foreach ($this->healthReportProvider->report()->checks() as $check) {
            $data = $check->toSafeArray();
            $status = $check->status();
            $severity = $check->severity();
            $blocking = $check->blocking();

            if (!$blocking && 'healthy' === $status && 'info' === $severity) {
                continue;
            }

            $issues[] = new AccessUserAdministrationDiagnosticIssue(
                self::stringValue($data['key'] ?? null, 'unknown'),
                self::stringValue($data['label'] ?? null, 'Unknown'),
                self::stringValue($data['category'] ?? null, 'diagnostics'),
                $severity,
                $status,
                $blocking,
                self::arrayValue($data['context'] ?? null),
            );
        }

        if ([] === $issues) {
            $issues[] = new AccessUserAdministrationDiagnosticIssue(
                'accessing.diagnostics.clear',
                'Accessing user administration diagnostics have no active blocking issue',
                'diagnostics',
                'info',
                'clear',
                false,
                [
                    'owner' => 'Accessing',
                    'administeringRole' => 'safe_visualizer',
                ],
            );
        }

        return new AccessUserAdministrationDiagnosticReport(
            new \DateTimeImmutable(),
            $issues,
            [
                'Diagnostics do not expose password hashes, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
                'Accessing remains the owner of user/session/authentication execution.',
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
