<?php

declare(strict_types=1);

namespace App\Accessing\Dto;

final readonly class AccessPasskeyAssertionResult
{
    public function __construct(public string $credentialId, public string $userHandle, public int $signCount, public ?string $credentialRecord = null)
    {
        if ('' === trim($credentialId) || '' === trim($userHandle)) {
            throw new \InvalidArgumentException('Verified passkey assertion identifiers cannot be empty.');
        }

        if ($signCount < 0) {
            throw new \InvalidArgumentException('Verified passkey assertion sign count cannot be negative.');
        }
    }
}
