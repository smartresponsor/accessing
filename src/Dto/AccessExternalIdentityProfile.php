<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

final readonly class AccessExternalIdentityProfile
{
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
