<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\Dto\AccessPasskeyAttestationResult;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Entity\AccessEntity;

interface AccessPasskeyAttestationVerifierInterface
{
    /** @param array<string, mixed> $credentialResponse */
    public function verify(
        array $credentialResponse,
        string $expectedChallenge,
        AccessPasskeyRelyingPartyConfig $relyingParty,
        AccessEntity $user,
    ): AccessPasskeyAttestationResult;
}
