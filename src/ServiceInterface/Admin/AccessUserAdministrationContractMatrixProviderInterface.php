<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationContractMatrix;

interface AccessUserAdministrationContractMatrixProviderInterface
{
    public function matrix(): AccessUserAdministrationContractMatrix;
}
