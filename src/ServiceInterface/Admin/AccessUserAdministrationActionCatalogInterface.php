<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Admin;

use App\Accessing\Value\Admin\AccessUserAdministrationActionDescriptor;

interface AccessUserAdministrationActionCatalogInterface
{
    /** @return list<AccessUserAdministrationActionDescriptor> */
    public function descriptors(): array;
}
