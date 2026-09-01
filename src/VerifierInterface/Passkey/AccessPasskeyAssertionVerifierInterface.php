<?php

declare(strict_types=1);

namespace App\Accessing\VerifierInterface\Passkey;

use App\Accessing\Dto\AccessPasskeyAssertionResult;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;

interface AccessPasskeyAssertionVerifierInterface
{
    /** @param array<string, mixed> $credentialResponse */
    public function verify(
        array $credentialResponse,
        string $expectedChallenge,
        AccessPasskeyRelyingPartyConfig $relyingParty,
        string $storedPublicKey,
        string $storedUserHandle,
        ?string $storedCredentialRecord = null,
    ): AccessPasskeyAssertionResult;
}
