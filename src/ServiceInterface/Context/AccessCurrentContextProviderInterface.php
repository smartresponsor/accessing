<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Context;

use App\Accessing\Context\AccessCurrentContext;

interface AccessCurrentContextProviderInterface
{
    public function current(): ?AccessCurrentContext;
}
