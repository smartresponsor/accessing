<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\Dto\AccessPasskeyRegistrationOptions;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use Symfony\Component\HttpFoundation\Request;

interface AccessPasskeyRegistrationServiceInterface
{
    public function issueOptions(
        AccessEntity $user,
        AccessPasskeyRelyingPartyConfig $relyingParty,
    ): AccessPasskeyRegistrationOptions;

    /** @param array<string, mixed> $credentialResponse */
    public function complete(
        AccessEntity $user,
        AccessPasskeyRelyingPartyConfig $relyingParty,
        array $credentialResponse,
        string $name,
        ?Request $request = null,
    ): AccessPasskeyCredentialEntity;
}
