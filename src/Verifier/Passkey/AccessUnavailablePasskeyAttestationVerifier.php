<?php

declare(strict_types=1);

namespace App\Accessing\Verifier\Passkey;

use App\Accessing\DTO\AccessPasskeyAttestationResultDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAttestationVerifierInterface;

/**
 * Defines the unavailable passkey attestation verifier type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessUnavailablePasskeyAttestationVerifier implements AccessPasskeyAttestationVerifierInterface
{
    /**
     * Executes the verify operation within the canonical Accessing component workflow.
     */
    public function verify(
        array $credentialResponse,
        string $expectedChallenge,
        AccessPasskeyRelyingPartyConfigDTO $relyingParty,
        AccessEntity $user,
    ): AccessPasskeyAttestationResultDTO {
        throw new AccessPasskeyVerificationUnavailableException();
    }
}
