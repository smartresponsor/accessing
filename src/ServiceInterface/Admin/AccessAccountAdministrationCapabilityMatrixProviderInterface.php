<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationCapabilityMatrix;

interface AccessAccountAdministrationCapabilityMatrixProviderInterface
{
    public function matrix(): AccessAccountAdministrationCapabilityMatrix;
}
