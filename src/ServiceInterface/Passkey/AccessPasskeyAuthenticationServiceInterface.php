<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\Dto\AccessPasskeyAuthenticationOptions;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;

interface AccessPasskeyAuthenticationServiceInterface
{
    public function issueOptions(AccessPasskeyRelyingPartyConfig $relyingParty, ?AccessEntity $user = null): AccessPasskeyAuthenticationOptions;

    /** @param array<string, mixed> $credentialResponse */
    public function complete(AccessPasskeyRelyingPartyConfig $relyingParty, array $credentialResponse, ?Request $request = null): AccessEntity;
}
