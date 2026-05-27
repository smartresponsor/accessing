<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationAction;

/**
 * Contract for controlled account administration initiated by Administering.
 *
 * This keeps Accessing as the owner of login, session, password, 2FA, and
 * security event semantics while allowing an admin UI to request safe actions.
 */
interface AccessingAccountAdministrationServiceInterface
{
    public function supports(string $action): bool;

    public function execute(AccessingAccountAdministrationAction $action): void;
}
