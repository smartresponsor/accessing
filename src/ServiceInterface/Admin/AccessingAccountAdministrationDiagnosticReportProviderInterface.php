<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationDiagnosticReport;

interface AccessingAccountAdministrationDiagnosticReportProviderInterface
{
    public function report(): AccessingAccountAdministrationDiagnosticReport;
}
