<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessAccountAdministrationResult;

interface AccessAccountAdministrationRequestValidatorInterface
{
    public function validate(AccessAccountAdministrationRequest $request): AccessAccountAdministrationResult;
}
