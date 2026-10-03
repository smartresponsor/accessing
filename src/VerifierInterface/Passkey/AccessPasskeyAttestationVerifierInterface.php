<?php

declare(strict_types=1);

namespace App\Accessing\VerifierInterface\Passkey;

use App\Accessing\DTO\AccessPasskeyAttestationResultDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\Access\AccessEntity;

/**
 * Defines the passkey attestation verifier interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPasskeyAttestationVerifierInterface
{
    /** @param array<string, mixed> $credentialResponse */
    public function verify(
        array $credentialResponse,
        string $expectedChallenge,
        AccessPasskeyRelyingPartyConfigDTO $relyingParty,
        AccessEntity $user,
    ): AccessPasskeyAttestationResultDTO;
}
