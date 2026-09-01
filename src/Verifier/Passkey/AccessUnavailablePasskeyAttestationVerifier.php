<?php

declare(strict_types=1);

namespace App\Accessing\Verifier\Passkey;

use App\Accessing\Dto\AccessPasskeyAttestationResult;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\VerifierInterface\Passkey\AccessPasskeyAttestationVerifierInterface;

final readonly class AccessUnavailablePasskeyAttestationVerifier implements AccessPasskeyAttestationVerifierInterface
{
    public function verify(
        array $credentialResponse,
        string $expectedChallenge,
        AccessPasskeyRelyingPartyConfig $relyingParty,
        AccessEntity $user,
    ): AccessPasskeyAttestationResult {
        throw new AccessPasskeyVerificationUnavailableException();
    }
}
