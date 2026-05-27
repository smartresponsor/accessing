<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationReadinessReport;

interface AccessingAccountAdministrationReadinessReportProviderInterface
{
    public function report(int $auditLimit = 100): AccessingAccountAdministrationReadinessReport;
}
