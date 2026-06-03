<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\AccountContext;

use App\Accessing\Context\AccessingCurrentAccountContext;

interface AccessingCurrentAccountContextProviderInterface
{
    public function current(): ?AccessingCurrentAccountContext;
}
