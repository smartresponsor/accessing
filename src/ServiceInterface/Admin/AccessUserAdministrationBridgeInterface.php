<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationAction;
use App\Accessing\Value\Admin\AccessUserAdministrationRequest;
use App\Accessing\Value\Admin\AccessUserAdministrationResult;

/**
 * Safe facade for Administering-driven Accessing user actions.
 */
interface AccessUserAdministrationBridgeInterface
{
    public function execute(AccessUserAdministrationAction $action): AccessUserAdministrationResult;

    public function executeRequest(AccessUserAdministrationRequest $request): AccessUserAdministrationResult;
}
