<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationProjection;

/**
 * Provides safe account projections for Administering without exposing Accessing internals.
 */
interface AccessAccountAdministrationProjectionProviderInterface
{
    /** @return list<AccessAccountAdministrationProjection> */
    public function recent(int $limit = 25): array;

    public function findBySubjectId(string $subjectId): ?AccessAccountAdministrationProjection;
}
