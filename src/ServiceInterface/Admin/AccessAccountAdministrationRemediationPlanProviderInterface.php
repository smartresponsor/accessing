<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationRemediationPlan;

interface AccessAccountAdministrationRemediationPlanProviderInterface
{
    public function plan(): AccessAccountAdministrationRemediationPlan;
}
