<?php

declare(strict_types=1);

namespace App\Accessing\VerifierInterface\Passkey;

use App\Accessing\DTO\AccessPasskeyAssertionResultDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;

/**
 * Defines the passkey assertion verifier interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPasskeyAssertionVerifierInterface
{
    /** @param array<string, mixed> $credentialResponse */
    public function verify(
        array $credentialResponse,
        string $expectedChallenge,
        AccessPasskeyRelyingPartyConfigDTO $relyingParty,
        string $storedPublicKey,
        string $storedUserHandle,
        ?string $storedCredentialRecord = null,
    ): AccessPasskeyAssertionResultDTO;
}
