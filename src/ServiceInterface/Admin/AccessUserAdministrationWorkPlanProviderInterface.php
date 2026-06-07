<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationWorkPlan;

interface AccessUserAdministrationWorkPlanProviderInterface
{
    public function plan(): AccessUserAdministrationWorkPlan;
}
