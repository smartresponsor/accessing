<?php

declare(strict_types=1);

namespace App\Accessing\Service\Passkey;

use App\Accessing\Dto\AccessPasskeyAssertionResult;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Exception\AccessPasskeyVerificationUnavailableException;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAssertionVerifierInterface;

final readonly class AccessFailClosedPasskeyAssertionVerifier implements AccessPasskeyAssertionVerifierInterface
{
    public function verify(array $credentialResponse, string $expectedChallenge, AccessPasskeyRelyingPartyConfig $relyingParty, string $storedPublicKey, string $storedUserHandle, ?string $storedCredentialRecord = null): AccessPasskeyAssertionResult
    {
        throw new AccessPasskeyVerificationUnavailableException();
    }
}
