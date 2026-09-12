<?php

declare(strict_types=1);

namespace App\Accessing\Verifier\Passkey;

use App\Accessing\DTO\AccessPasskeyAssertionResultDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAssertionVerifierInterface;

/**
 * Defines the fail closed passkey assertion verifier type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessFailClosedPasskeyAssertionVerifier implements AccessPasskeyAssertionVerifierInterface
{
    /**
     * Executes the verify operation within the canonical Accessing component workflow.
     */
    public function verify(array $credentialResponse, string $expectedChallenge, AccessPasskeyRelyingPartyConfigDTO $relyingParty, string $storedPublicKey, string $storedUserHandle, ?string $storedCredentialRecord = null): AccessPasskeyAssertionResultDTO
    {
        throw new AccessPasskeyVerificationUnavailableException();
    }
}
