<?php

declare(strict_types=1);

namespace App\Accessing\Recorder\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationAuditRecorderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditEvent;

/**
 * Bootstrap audit recorder until host/system storage wiring is provided.
 */
final class AccessNullAccessingAccountAdministrationAuditRecorder implements AccessAccountAdministrationAuditRecorderInterface
{
    public function record(AccessAccountAdministrationAuditEvent $event): void
    {
        unset($event);
    }
}
