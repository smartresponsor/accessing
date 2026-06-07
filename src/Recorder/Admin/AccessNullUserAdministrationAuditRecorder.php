<?php

declare(strict_types=1);

namespace App\Accessing\Recorder\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationAuditRecorderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditEvent;

/**
 * Bootstrap audit recorder until host/system storage wiring is provided.
 */
final class AccessNullUserAdministrationAuditRecorder implements AccessUserAdministrationAuditRecorderInterface
{
    public function record(AccessUserAdministrationAuditEvent $event): void
    {
        unset($event);
    }
}
