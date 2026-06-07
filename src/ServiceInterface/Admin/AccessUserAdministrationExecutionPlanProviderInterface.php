<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationExecutionPlan;

interface AccessUserAdministrationExecutionPlanProviderInterface
{
    public function plan(): AccessUserAdministrationExecutionPlan;
}
