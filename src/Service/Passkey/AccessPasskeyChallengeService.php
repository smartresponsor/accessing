<?php

declare(strict_types=1);

namespace App\Accessing\Service\Passkey;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\RepositoryInterface\AccessPasskeyChallengeRepositoryInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyChallengeServiceInterface;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use Psr\Clock\ClockInterface;

final readonly class AccessPasskeyChallengeService implements AccessPasskeyChallengeServiceInterface
{
    public function __construct(
        private AccessPasskeyChallengeRepositoryInterface $challengeRepository,
        private ClockInterface $clock,
        private int $accessingPasskeyChallengeTtlSeconds = 300,
    ) {
        if ($this->accessingPasskeyChallengeTtlSeconds < 30) {
            throw new \InvalidArgumentException('Passkey challenge TTL must be at least 30 seconds.');
        }
    }

    /** @return array{challenge: string, state: AccessPasskeyChallengeEntity} */
    public function issue(
        AccessPasskeyCeremonyPurpose $purpose,
        string $relyingPartyId,
        string $origin,
        ?AccessEntity $user = null,
    ): array {
        $plainChallenge = $this->base64UrlEncode(random_bytes(32));
        $createdAt = $this->clock->now();
        $challenge = new AccessPasskeyChallengeEntity(
            $plainChallenge,
            $purpose,
            $relyingPartyId,
            $origin,
            $createdAt,
            $createdAt->modify(sprintf('+%d seconds', $this->accessingPasskeyChallengeTtlSeconds)),
            $user,
        );
        $this->challengeRepository->save($challenge, true);

        return ['challenge' => $plainChallenge, 'state' => $challenge];
    }

    public function consume(
        string $plainChallenge,
        AccessPasskeyCeremonyPurpose $purpose,
        string $relyingPartyId,
        string $origin,
    ): AccessPasskeyChallengeEntity {
        $challenge = $this->challengeRepository->findOneByChallengeHash(hash('sha256', $plainChallenge));
        $now = $this->clock->now();

        if (!$challenge instanceof AccessPasskeyChallengeEntity
            || !$challenge->isUsable($plainChallenge, $purpose, $relyingPartyId, $origin, $now)) {
            throw new \DomainException('Passkey challenge is invalid, expired, or already consumed.');
        }

        $challenge->consume($now);
        $this->challengeRepository->save($challenge, true);

        return $challenge;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
