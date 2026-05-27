<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationExecutionPlan;

interface AccessingAccountAdministrationExecutionPlanProviderInterface
{
    public function plan(): AccessingAccountAdministrationExecutionPlan;
}
