<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationHealthReport;

/**
 * Provides a safe health report for Administering/host diagnostics.
 */
interface AccessingAccountAdministrationHealthReportProviderInterface
{
    public function report(): AccessingAccountAdministrationHealthReport;
}
