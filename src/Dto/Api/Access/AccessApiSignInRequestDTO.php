<?php

declare(strict_types=1);

namespace App\Accessing\DTO\Api\Access;

/**
 * Defines the api sign in request dto type and its canonical responsibility within the Accessing component.
 */
final class AccessApiSignInRequestDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        public string $email = '',
        public string $password = '',
    ) {
    }
}
