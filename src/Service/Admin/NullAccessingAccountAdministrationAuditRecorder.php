<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationAuditRecorderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditEvent;

/**
 * Bootstrap audit recorder until host/system storage wiring is provided.
 */
final class NullAccessingAccountAdministrationAuditRecorder implements AccessingAccountAdministrationAuditRecorderInterface
{
    public function record(AccessingAccountAdministrationAuditEvent $event): void
    {
        unset($event);
    }
}
