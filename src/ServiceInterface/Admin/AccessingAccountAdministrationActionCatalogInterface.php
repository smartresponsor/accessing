<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessingAccountAdministrationActionDescriptor;

interface AccessingAccountAdministrationActionCatalogInterface
{
    /** @return list<AccessingAccountAdministrationActionDescriptor> */
    public function descriptors(): array;
}
