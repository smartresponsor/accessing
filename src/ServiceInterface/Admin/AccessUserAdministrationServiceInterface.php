<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationAction;

/**
 * Contract for controlled user administration initiated by Administering.
 *
 * This keeps Accessing as the owner of login, session, password, 2FA, and
 * security event semantics while allowing an admin UI to request safe actions.
 */
interface AccessUserAdministrationServiceInterface
{
    public function supports(string $action): bool;

    public function execute(AccessUserAdministrationAction $action): void;
}
