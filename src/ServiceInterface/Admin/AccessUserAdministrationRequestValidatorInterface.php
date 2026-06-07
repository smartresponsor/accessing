<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationRequest;
use App\Accessing\Value\Admin\AccessUserAdministrationResult;

interface AccessUserAdministrationRequestValidatorInterface
{
    public function validate(AccessUserAdministrationRequest $request): AccessUserAdministrationResult;
}
