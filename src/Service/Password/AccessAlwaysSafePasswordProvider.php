<?php

declare(strict_types=1);

namespace App\Accessing\Service\Password;

use App\Accessing\Dto\AccessPasswordSafetyResult;
use App\Accessing\ServiceInterface\Password\AccessCompromisedPasswordProviderInterface;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;

final readonly class AccessAlwaysSafePasswordProvider implements AccessCompromisedPasswordProviderInterface
{
    public function check(string $plainPassword): AccessPasswordSafetyResult
    {
        return new AccessPasswordSafetyResult(AccessPasswordSafetyStatus::Safe);
    }
}
