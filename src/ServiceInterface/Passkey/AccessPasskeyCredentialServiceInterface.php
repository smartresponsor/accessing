<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;

interface AccessPasskeyCredentialServiceInterface
{
    /** @param list<string> $transports */
    public function register(
        AccessEntity $user,
        string $credentialId,
        string $userHandle,
        string $publicKey,
        array $transports,
        string $name,
        int $signCount = 0,
        ?string $credentialRecord = null,
    ): AccessPasskeyCredentialEntity;

    public function recordSuccessfulAssertion(string $credentialId, int $signCount, ?string $credentialRecord = null): AccessPasskeyCredentialEntity;

    public function revoke(AccessEntity $user, string $credentialId): bool;
}
