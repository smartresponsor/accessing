<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationAuditEvent;

interface AccessAccountAdministrationAuditRecorderInterface
{
    public function record(AccessAccountAdministrationAuditEvent $event): void;
}
