<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;

/**
 * Defines the passkey credential service interface type and its canonical responsibility within the Accessing component.
 */
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

    /**
     * Executes the record successful assertion operation within the canonical Accessing component workflow.
     */
    public function recordSuccessfulAssertion(string $credentialId, int $signCount, ?string $credentialRecord = null): AccessPasskeyCredentialEntity;

    /**
     * Executes the revoke operation within the canonical Accessing component workflow.
     */
    public function revoke(AccessEntity $user, string $credentialId): bool;
}
