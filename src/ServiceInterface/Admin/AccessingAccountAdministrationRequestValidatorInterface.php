<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessingAccountAdministrationResult;

interface AccessingAccountAdministrationRequestValidatorInterface
{
    public function validate(AccessingAccountAdministrationRequest $request): AccessingAccountAdministrationResult;
}
