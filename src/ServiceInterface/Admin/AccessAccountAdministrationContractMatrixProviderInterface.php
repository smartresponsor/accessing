<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationContractMatrix;

interface AccessAccountAdministrationContractMatrixProviderInterface
{
    public function matrix(): AccessAccountAdministrationContractMatrix;
}
