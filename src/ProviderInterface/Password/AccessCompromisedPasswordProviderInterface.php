<?php

declare(strict_types=1);

namespace App\Accessing\ProviderInterface\Password;

use App\Accessing\Dto\AccessPasswordSafetyResult;

interface AccessCompromisedPasswordProviderInterface
{
    public function check(string $plainPassword): AccessPasswordSafetyResult;
}
