<?php

declare(strict_types=1);

namespace App\Accessing\DTO\Api\Access;

/**
 * Defines the api register request dto type and its canonical responsibility within the Accessing component.
 */
final class AccessApiRegisterRequestDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        public string $displayName = '',
        public string $email = '',
        public string $password = '',
    ) {
    }
}
