<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationContractMatrix;

interface AccessingAccountAdministrationContractMatrixProviderInterface
{
    public function matrix(): AccessingAccountAdministrationContractMatrix;
}
