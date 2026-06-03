<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationProjectionProviderInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationProjection;

/**
 * Empty bootstrap provider until the host wires Doctrine-backed account projections.
 */
final class BootstrapAccessingAccountAdministrationProjectionProvider implements AccessingAccountAdministrationProjectionProviderInterface
{
    /** @return list<AccessingAccountAdministrationProjection> */
    public function recent(int $limit = 25): array
    {
        return [];
    }

    public function findBySubjectId(string $subjectId): ?AccessingAccountAdministrationProjection
    {
        return null;
    }
}
