<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationDiagnosticReport;

interface AccessUserAdministrationDiagnosticReportProviderInterface
{
    public function report(): AccessUserAdministrationDiagnosticReport;
}
