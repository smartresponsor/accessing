<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationExecutionReadiness;

/**
 * Reports controlled account-action execution readiness without exposing security internals.
 */
final readonly class AccessAccountAdministrationExecutionReadinessProvider implements AccessAccountAdministrationExecutionReadinessProviderInterface
{
    public function report(): AccessAccountAdministrationExecutionReadiness
    {
        return new AccessAccountAdministrationExecutionReadiness(
            'bootstrap',
            true,
            [
                'safe action catalog',
                'request validation',
                'controlled bridge',
                'Doctrine-backed audit recording',
                'safe audit projections',
            ],
            [
                'lock account Doctrine mutation',
                'unlock account Doctrine mutation',
                'session termination mutation',
                '2FA reset request workflow',
            ],
        );
    }
}
