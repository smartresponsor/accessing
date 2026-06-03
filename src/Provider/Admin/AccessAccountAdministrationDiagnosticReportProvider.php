<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationDiagnosticReportProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationHealthReportProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationDiagnosticIssue;
use App\Accessing\Value\Admin\AccessAccountAdministrationDiagnosticReport;

/**
 * Builds a safe issue register from Accessing-owned account-administration health checks.
 */
final readonly class AccessAccountAdministrationDiagnosticReportProvider implements AccessAccountAdministrationDiagnosticReportProviderInterface
{
    public function __construct(
        private AccessAccountAdministrationHealthReportProviderInterface $healthReportProvider,
    ) {
    }

    public function report(): AccessAccountAdministrationDiagnosticReport
    {
        $issues = [];
        foreach ($this->healthReportProvider->report()->checks() as $check) {
            $data = $check->toSafeArray();
            $status = (string) $data['status'];
            $severity = (string) $data['severity'];
            $blocking = (bool) ($data['blocking'] ?? false);

            if (!$blocking && 'healthy' === $status && 'info' === $severity) {
                continue;
            }

            $issues[] = new AccessAccountAdministrationDiagnosticIssue(
                (string) $data['key'],
                (string) $data['label'],
                (string) $data['category'],
                $severity,
                $status,
                $blocking,
                is_array($data['context'] ?? null) ? $data['context'] : [],
            );
        }

        if ([] === $issues) {
            $issues[] = new AccessAccountAdministrationDiagnosticIssue(
                'accessing.diagnostics.clear',
                'Accessing account administration diagnostics have no active blocking issue',
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

        return new AccessAccountAdministrationDiagnosticReport(
            new \DateTimeImmutable(),
            $issues,
            [
                'Diagnostics do not expose password hashes, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
                'Accessing remains the owner of account/session/authentication execution.',
            ],
        );
    }
}
