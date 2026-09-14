<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\DTO\AccessPasskeyAuthenticationOptionsDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the passkey authentication service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPasskeyAuthenticationServiceInterface
{
    /**
     * Executes the issue options operation within the canonical Accessing component workflow.
     */
    public function issueOptions(AccessPasskeyRelyingPartyConfigDTO $relyingParty, ?AccessEntity $user = null): AccessPasskeyAuthenticationOptionsDTO;

    /** @param array<string, mixed> $credentialResponse */
    public function complete(AccessPasskeyRelyingPartyConfigDTO $relyingParty, array $credentialResponse, ?Request $request = null): AccessEntity;
}
