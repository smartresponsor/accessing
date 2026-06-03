<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\AccountContext;

use App\Accessing\Context\AccessCurrentAccountContext;

interface AccessCurrentAccountContextProviderInterface
{
    public function current(): ?AccessCurrentAccountContext;
}
