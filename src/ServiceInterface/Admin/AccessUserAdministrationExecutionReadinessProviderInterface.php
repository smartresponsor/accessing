<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationExecutionReadiness;

interface AccessUserAdministrationExecutionReadinessProviderInterface
{
    public function report(): AccessUserAdministrationExecutionReadiness;
}
