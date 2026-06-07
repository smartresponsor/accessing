<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationServiceInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationAction;

/**
 * Safe bootstrap implementation for host applications that have not wired
 * concrete user/session administration actions yet.
 */
final class AccessBootstrapUserAdministrationService implements AccessUserAdministrationServiceInterface
{
    private const SUPPORTED_ACTIONS = [
        'accessing.user.lock',
        'accessing.user.unlock',
        'accessing.session.terminate',
        'accessing.2fa.reset.request',
        'accessing.security_events.view',
    ];

    public function supports(string $action): bool
    {
        return in_array($action, self::SUPPORTED_ACTIONS, true);
    }

    public function execute(AccessUserAdministrationAction $action): void
    {
        if (!$this->supports($action->action())) {
            throw new \InvalidArgumentException(sprintf('Unsupported Accessing administration action "%s".', $action->action()));
        }
    }
}
