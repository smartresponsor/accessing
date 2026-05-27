<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditFilter;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditProjection;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditReport;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditSummary;

/**
 * Provides safe account-administration audit projections for Administering.
 */
interface AccessingAccountAdministrationAuditProjectionProviderInterface
{
    /** @return list<AccessingAccountAdministrationAuditProjection> */
    public function recent(int $limit = 50): array;

    /** @return list<AccessingAccountAdministrationAuditProjection> */
    public function matching(AccessingAccountAdministrationAuditFilter $filter): array;

    public function summary(int $limit = 200): AccessingAccountAdministrationAuditSummary;

    public function report(AccessingAccountAdministrationAuditFilter $filter): AccessingAccountAdministrationAuditReport;
}
