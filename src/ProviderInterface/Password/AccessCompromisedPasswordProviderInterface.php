<?php

declare(strict_types=1);

namespace App\Accessing\ProviderInterface\Password;

use App\Accessing\DTO\AccessPasswordSafetyResultDTO;

/**
 * Defines the compromised password provider interface type and its canonical responsibility within the Accessing component.
 */
interface AccessCompromisedPasswordProviderInterface
{
    /**
     * Executes the check operation within the canonical Accessing component workflow.
     */
    public function check(string $plainPassword): AccessPasswordSafetyResultDTO;
}
