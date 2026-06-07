<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationCapabilityMatrix;

interface AccessUserAdministrationCapabilityMatrixProviderInterface
{
    public function matrix(): AccessUserAdministrationCapabilityMatrix;
}
