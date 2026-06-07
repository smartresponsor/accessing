<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\UserContext;

use App\Accessing\Context\AccessCurrentUserContext;

interface AccessCurrentUserContextProviderInterface
{
    public function current(): ?AccessCurrentUserContext;
}
