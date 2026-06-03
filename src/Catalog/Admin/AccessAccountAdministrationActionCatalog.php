<?php

declare(strict_types=1);

namespace App\Accessing\Catalog\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationActionCatalogInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationActionDescriptor;

/**
 * Catalog of controlled Accessing account actions safe for Administering to display.
 */
final class AccessAccountAdministrationActionCatalog implements AccessAccountAdministrationActionCatalogInterface
{
    /** @return list<AccessAccountAdministrationActionDescriptor> */
    public function descriptors(): array
    {
        return [
            new AccessAccountAdministrationActionDescriptor('accessing.account.lock', 'Lock account', 'high'),
            new AccessAccountAdministrationActionDescriptor('accessing.account.unlock', 'Unlock account', 'high'),
            new AccessAccountAdministrationActionDescriptor('accessing.session.terminate', 'Terminate session', 'medium'),
            new AccessAccountAdministrationActionDescriptor('accessing.2fa.reset.request', 'Request 2FA reset', 'high'),
            new AccessAccountAdministrationActionDescriptor('accessing.security_events.view', 'View security events', 'low', false),
        ];
    }
}
