<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationWorkPlan;

interface AccessAccountAdministrationWorkPlanProviderInterface
{
    public function plan(): AccessAccountAdministrationWorkPlan;
}
