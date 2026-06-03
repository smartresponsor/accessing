<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationAction;
use App\Accessing\Value\Admin\AccessAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessAccountAdministrationResult;

/**
 * Safe facade for Administering-driven Accessing account actions.
 */
interface AccessAccountAdministrationBridgeInterface
{
    public function execute(AccessAccountAdministrationAction $action): AccessAccountAdministrationResult;

    public function executeRequest(AccessAccountAdministrationRequest $request): AccessAccountAdministrationResult;
}
