<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationProjection;

/**
 * Provides safe user projections for Administering without exposing Accessing internals.
 */
interface AccessUserAdministrationProjectionProviderInterface
{
    /** @return list<AccessUserAdministrationProjection> */
    public function recent(int $limit = 25): array;

    public function findBySubjectId(string $subjectId): ?AccessUserAdministrationProjection;
}
