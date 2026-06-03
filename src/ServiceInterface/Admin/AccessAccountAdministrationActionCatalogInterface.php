<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessAccountAdministrationActionDescriptor;

interface AccessAccountAdministrationActionCatalogInterface
{
    /** @return list<AccessAccountAdministrationActionDescriptor> */
    public function descriptors(): array;
}
