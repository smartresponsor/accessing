<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;

/**
 * Defines the passkey challenge service interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPasskeyChallengeServiceInterface
{
    /** @return array{challenge: string, state: AccessPasskeyChallengeEntity} */
    public function issue(
        AccessPasskeyCeremonyPurpose $purpose,
        string $relyingPartyId,
        string $origin,
        ?AccessEntity $user = null,
    ): array;

    /**
     * Executes the consume operation within the canonical Accessing component workflow.
     */
    public function consume(
        string $plainChallenge,
        AccessPasskeyCeremonyPurpose $purpose,
        string $relyingPartyId,
        string $origin,
    ): AccessPasskeyChallengeEntity;
}
