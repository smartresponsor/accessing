<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationReadinessReport;

interface AccessAccountAdministrationReadinessReportProviderInterface
{
    public function report(int $auditLimit = 100): AccessAccountAdministrationReadinessReport;
}
