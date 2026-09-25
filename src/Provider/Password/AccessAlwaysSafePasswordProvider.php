<?php

declare(strict_types=1);

namespace App\Accessing\Provider\Password;

use App\Accessing\DTO\AccessPasswordSafetyResultDTO;
use App\Accessing\ProviderInterface\Password\AccessCompromisedPasswordProviderInterface;
use App\Accessing\ValueObject\AccessPasswordSafetyStatus;

/**
 * Defines the always safe password provider type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessAlwaysSafePasswordProvider implements AccessCompromisedPasswordProviderInterface
{
    /**
     * Executes the check operation within the canonical Accessing component workflow.
     */
    public function check(string $plainPassword): AccessPasswordSafetyResultDTO
    {
        return new AccessPasswordSafetyResultDTO(AccessPasswordSafetyStatus::Safe);
    }
}
