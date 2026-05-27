<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationExecutionReadinessProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationExecutionReadiness;

/**
 * Reports controlled account-action execution readiness without exposing security internals.
 */
final readonly class AccessingAccountAdministrationExecutionReadinessProvider implements AccessingAccountAdministrationExecutionReadinessProviderInterface
{
    public function report(): AccessingAccountAdministrationExecutionReadiness
    {
        return new AccessingAccountAdministrationExecutionReadiness(
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
