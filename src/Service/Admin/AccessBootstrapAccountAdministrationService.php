<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationServiceInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationAction;

/**
 * Safe bootstrap implementation for host applications that have not wired
 * concrete account/session administration actions yet.
 */
final class AccessBootstrapAccountAdministrationService implements AccessAccountAdministrationServiceInterface
{
    private const SUPPORTED_ACTIONS = [
        'accessing.account.lock',
        'accessing.account.unlock',
        'accessing.session.terminate',
        'accessing.2fa.reset.request',
        'accessing.security_events.view',
    ];

    public function supports(string $action): bool
    {
        return in_array($action, self::SUPPORTED_ACTIONS, true);
    }

    public function execute(AccessAccountAdministrationAction $action): void
    {
        if (!$this->supports($action->action())) {
            throw new \InvalidArgumentException(sprintf('Unsupported Accessing administration action "%s".', $action->action()));
        }
    }
}
