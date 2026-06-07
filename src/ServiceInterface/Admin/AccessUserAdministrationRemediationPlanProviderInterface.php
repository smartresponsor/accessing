<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationRemediationPlan;

interface AccessUserAdministrationRemediationPlanProviderInterface
{
    public function plan(): AccessUserAdministrationRemediationPlan;
}
