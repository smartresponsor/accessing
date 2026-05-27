<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationRemediationPlan;

interface AccessingAccountAdministrationRemediationPlanProviderInterface
{
    public function plan(): AccessingAccountAdministrationRemediationPlan;
}
