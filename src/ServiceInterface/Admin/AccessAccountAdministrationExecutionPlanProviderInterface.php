<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationExecutionPlan;

interface AccessAccountAdministrationExecutionPlanProviderInterface
{
    public function plan(): AccessAccountAdministrationExecutionPlan;
}
