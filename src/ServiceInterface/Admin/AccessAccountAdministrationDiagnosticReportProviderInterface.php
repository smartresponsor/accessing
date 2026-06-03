<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationDiagnosticReport;

interface AccessAccountAdministrationDiagnosticReportProviderInterface
{
    public function report(): AccessAccountAdministrationDiagnosticReport;
}
