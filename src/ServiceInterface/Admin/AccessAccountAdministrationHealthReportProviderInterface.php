<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationHealthReport;

/**
 * Provides a safe health report for Administering/host diagnostics.
 */
interface AccessAccountAdministrationHealthReportProviderInterface
{
    public function report(): AccessAccountAdministrationHealthReport;
}
