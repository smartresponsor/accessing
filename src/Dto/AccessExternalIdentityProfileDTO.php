<?php

declare(strict_types=1);

namespace App\Accessing\DTO;

/**
 * Defines the external identity profile dto type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessExternalIdentityProfileDTO
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        public string $provider,
        public string $subject,
        public string $email,
        public bool $emailVerified,
        public ?string $displayName = null,
        public ?string $avatarUrl = null,
    ) {
        if ('' === trim($provider) || '' === trim($subject) || '' === trim($email)) {
            throw new \InvalidArgumentException('External identity profile requires provider, subject, and email.');
        }
    }
}
