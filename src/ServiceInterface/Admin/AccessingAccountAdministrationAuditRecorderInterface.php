<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditEvent;

interface AccessingAccountAdministrationAuditRecorderInterface
{
    public function record(AccessingAccountAdministrationAuditEvent $event): void;
}
