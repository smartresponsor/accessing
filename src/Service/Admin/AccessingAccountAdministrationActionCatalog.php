<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationActionCatalogInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationActionDescriptor;

/**
 * Catalog of controlled Accessing account actions safe for Administering to display.
 */
final class AccessingAccountAdministrationActionCatalog implements AccessingAccountAdministrationActionCatalogInterface
{
    /** @return list<AccessingAccountAdministrationActionDescriptor> */
    public function descriptors(): array
    {
        return [
            new AccessingAccountAdministrationActionDescriptor('accessing.account.lock', 'Lock account', 'high'),
            new AccessingAccountAdministrationActionDescriptor('accessing.account.unlock', 'Unlock account', 'high'),
            new AccessingAccountAdministrationActionDescriptor('accessing.session.terminate', 'Terminate session', 'medium'),
            new AccessingAccountAdministrationActionDescriptor('accessing.2fa.reset.request', 'Request 2FA reset', 'high'),
            new AccessingAccountAdministrationActionDescriptor('accessing.security_events.view', 'View security events', 'low', false),
        ];
    }
}
