<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationReadinessReport;

interface AccessUserAdministrationReadinessReportProviderInterface
{
    public function report(int $auditLimit = 100): AccessUserAdministrationReadinessReport;
}
