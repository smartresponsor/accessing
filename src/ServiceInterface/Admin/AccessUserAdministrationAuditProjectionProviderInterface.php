<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationAuditFilter;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditProjection;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditReport;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditSummary;

/**
 * Provides safe user-administration audit projections for Administering.
 */
interface AccessUserAdministrationAuditProjectionProviderInterface
{
    /** @return list<AccessUserAdministrationAuditProjection> */
    public function recent(int $limit = 50): array;

    /** @return list<AccessUserAdministrationAuditProjection> */
    public function matching(AccessUserAdministrationAuditFilter $filter): array;

    public function summary(int $limit = 200): AccessUserAdministrationAuditSummary;

    public function report(AccessUserAdministrationAuditFilter $filter): AccessUserAdministrationAuditReport;
}
