<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationHealthReport;

/**
 * Provides a safe health report for Administering/host diagnostics.
 */
interface AccessUserAdministrationHealthReportProviderInterface
{
    public function report(): AccessUserAdministrationHealthReport;
}
