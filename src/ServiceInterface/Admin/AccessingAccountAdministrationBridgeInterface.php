<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationAction;
use App\Accessing\Value\Admin\AccessingAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessingAccountAdministrationResult;

/**
 * Safe facade for Administering-driven Accessing account actions.
 */
interface AccessingAccountAdministrationBridgeInterface
{
    public function execute(AccessingAccountAdministrationAction $action): AccessingAccountAdministrationResult;

    public function executeRequest(AccessingAccountAdministrationRequest $request): AccessingAccountAdministrationResult;
}
