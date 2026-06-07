<?php

declare(strict_types=1);

namespace App\Accessing\Catalog\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationActionCatalogInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationActionDescriptor;

/**
 * Catalog of controlled Accessing user actions safe for Administering to display.
 */
final class AccessUserAdministrationActionCatalog implements AccessUserAdministrationActionCatalogInterface
{
    /** @return list<AccessUserAdministrationActionDescriptor> */
    public function descriptors(): array
    {
        return [
            new AccessUserAdministrationActionDescriptor('accessing.user.lock', 'Lock user', 'high'),
            new AccessUserAdministrationActionDescriptor('accessing.user.unlock', 'Unlock user', 'high'),
            new AccessUserAdministrationActionDescriptor('accessing.session.terminate', 'Terminate session', 'medium'),
            new AccessUserAdministrationActionDescriptor('accessing.2fa.reset.request', 'Request 2FA reset', 'high'),
            new AccessUserAdministrationActionDescriptor('accessing.security_events.view', 'View security events', 'low', false),
        ];
    }
}
