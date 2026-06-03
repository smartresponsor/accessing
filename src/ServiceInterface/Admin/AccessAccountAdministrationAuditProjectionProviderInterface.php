<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationAuditFilter;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditProjection;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditReport;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditSummary;

/**
 * Provides safe account-administration audit projections for Administering.
 */
interface AccessAccountAdministrationAuditProjectionProviderInterface
{
    /** @return list<AccessAccountAdministrationAuditProjection> */
    public function recent(int $limit = 50): array;

    /** @return list<AccessAccountAdministrationAuditProjection> */
    public function matching(AccessAccountAdministrationAuditFilter $filter): array;

    public function summary(int $limit = 200): AccessAccountAdministrationAuditSummary;

    public function report(AccessAccountAdministrationAuditFilter $filter): AccessAccountAdministrationAuditReport;
}
