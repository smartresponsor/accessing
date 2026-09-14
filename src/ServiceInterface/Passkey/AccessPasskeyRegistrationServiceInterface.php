<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\DTO\AccessPasskeyRegistrationOptionsDTO;
use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines the passkey registration service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPasskeyRegistrationServiceInterface
{
    /**
     * Executes the issue options operation within the canonical Accessing component workflow.
     */
    public function issueOptions(
        AccessEntity $user,
        AccessPasskeyRelyingPartyConfigDTO $relyingParty,
    ): AccessPasskeyRegistrationOptionsDTO;

    /** @param array<string, mixed> $credentialResponse */
    public function complete(
        AccessEntity $user,
        AccessPasskeyRelyingPartyConfigDTO $relyingParty,
        array $credentialResponse,
        string $name,
        ?Request $request = null,
    ): AccessPasskeyCredentialEntity;
}
