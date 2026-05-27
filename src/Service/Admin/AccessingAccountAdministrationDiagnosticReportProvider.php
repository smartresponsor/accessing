<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationDiagnosticReportProviderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationHealthReportProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationDiagnosticIssue;
use App\Accessing\Value\Admin\AccessingAccountAdministrationDiagnosticReport;

/**
 * Builds a safe issue register from Accessing-owned account-administration health checks.
 */
final readonly class AccessingAccountAdministrationDiagnosticReportProvider implements AccessingAccountAdministrationDiagnosticReportProviderInterface
{
    public function __construct(
        private AccessingAccountAdministrationHealthReportProviderInterface $healthReportProvider,
    ) {
    }

    public function report(): AccessingAccountAdministrationDiagnosticReport
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

            $issues[] = new AccessingAccountAdministrationDiagnosticIssue(
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
            $issues[] = new AccessingAccountAdministrationDiagnosticIssue(
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

        return new AccessingAccountAdministrationDiagnosticReport(
            new \DateTimeImmutable(),
            $issues,
            [
                'Diagnostics do not expose password hashes, TOTP secrets, recovery codes, reset tokens, or raw session payloads.',
                'Accessing remains the owner of account/session/authentication execution.',
            ],
        );
    }
}
