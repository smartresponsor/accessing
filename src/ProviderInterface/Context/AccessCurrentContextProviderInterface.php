<?php

declare(strict_types=1);

namespace App\Accessing\ProviderInterface\Context;

use App\Accessing\Context\AccessCurrentContext;

interface AccessCurrentContextProviderInterface
{
    public function current(): ?AccessCurrentContext;
}
