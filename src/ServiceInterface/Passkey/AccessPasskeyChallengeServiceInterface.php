<?php

declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Passkey;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;

interface AccessPasskeyChallengeServiceInterface
{
    /** @return array{challenge: string, state: AccessPasskeyChallengeEntity} */
    public function issue(
        AccessPasskeyCeremonyPurpose $purpose,
        string $relyingPartyId,
        string $origin,
        ?AccessEntity $user = null,
    ): array;

    public function consume(
        string $plainChallenge,
        AccessPasskeyCeremonyPurpose $purpose,
        string $relyingPartyId,
        string $origin,
    ): AccessPasskeyChallengeEntity;
}
