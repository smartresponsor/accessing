<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationExecutionReadinessProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationExecutionReadiness;

/**
 * Reports controlled user-action execution readiness without exposing security internals.
 */
final readonly class AccessUserAdministrationExecutionReadinessProvider implements AccessUserAdministrationExecutionReadinessProviderInterface
{
    public function report(): AccessUserAdministrationExecutionReadiness
    {
        return new AccessUserAdministrationExecutionReadiness(
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
                'lock user Doctrine mutation',
                'unlock user Doctrine mutation',
                'session termination mutation',
                '2FA reset request workflow',
            ],
        );
    }
}
