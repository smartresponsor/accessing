<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationProjection;

/**
 * Empty bootstrap provider until the host wires Doctrine-backed user projections.
 */
final class AccessBootstrapUserAdministrationProjectionProvider implements AccessUserAdministrationProjectionProviderInterface
{
    /** @return list<AccessUserAdministrationProjection> */
    public function recent(int $limit = 25): array
    {
        return [];
    }

    public function findBySubjectId(string $subjectId): ?AccessUserAdministrationProjection
    {
        return null;
    }
}
