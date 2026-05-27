<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationCapabilityMatrix;

interface AccessingAccountAdministrationCapabilityMatrixProviderInterface
{
    public function matrix(): AccessingAccountAdministrationCapabilityMatrix;
}
