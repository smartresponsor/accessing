<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationExecutionReadiness;

interface AccessingAccountAdministrationExecutionReadinessProviderInterface
{
    public function report(): AccessingAccountAdministrationExecutionReadiness;
}
