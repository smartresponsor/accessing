<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationAuditEvent;

interface AccessUserAdministrationAuditRecorderInterface
{
    public function record(AccessUserAdministrationAuditEvent $event): void;
}
