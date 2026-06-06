<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationProjection;

/**
 * Empty bootstrap provider until the host wires Doctrine-backed account projections.
 */
final class AccessBootstrapAccountAdministrationProjectionProvider implements AccessAccountAdministrationProjectionProviderInterface
{
    /** @return list<AccessAccountAdministrationProjection> */
    public function recent(int $limit = 25): array
    {
        return [];
    }

    public function findBySubjectId(string $subjectId): ?AccessAccountAdministrationProjection
    {
        return null;
    }
}
