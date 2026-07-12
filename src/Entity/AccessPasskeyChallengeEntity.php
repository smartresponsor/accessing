<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessPasskeyChallengeRepository;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessPasskeyChallengeRepository::class)]
#[ORM\Table(name: 'access_passkey_challenge')]
#[ORM\UniqueConstraint(name: 'uniq_access_passkey_challenge_hash', columns: ['challenge_hash'])]
final class AccessPasskeyChallengeEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?AccessEntity $user;

    #[ORM\Column(name: 'challenge_hash', length: 64)]
    private string $challengeHash;

    #[ORM\Column(length: 32, enumType: AccessPasskeyCeremonyPurpose::class)]
    private AccessPasskeyCeremonyPurpose $purpose;

    #[ORM\Column(length: 255)]
    private string $relyingPartyId;

    #[ORM\Column(length: 2048)]
    private string $origin;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    public function __construct(
        string $plainChallenge,
        AccessPasskeyCeremonyPurpose $purpose,
        string $relyingPartyId,
        string $origin,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $expiresAt,
        ?AccessEntity $user = null,
    ) {
        if ('' === $plainChallenge || '' === trim($relyingPartyId) || '' === trim($origin)) {
            throw new \InvalidArgumentException('Passkey challenge binding cannot contain empty values.');
        }

        if ($expiresAt <= $createdAt) {
            throw new \InvalidArgumentException('Passkey challenge expiry must be after creation.');
        }

        if (AccessPasskeyCeremonyPurpose::Registration === $purpose && !$user instanceof AccessEntity) {
            throw new \InvalidArgumentException('Passkey registration challenge requires an access user.');
        }

        $this->challengeHash = hash('sha256', $plainChallenge);
        $this->purpose = $purpose;
        $this->relyingPartyId = trim($relyingPartyId);
        $this->origin = rtrim(trim($origin), '/');
        $this->createdAt = $createdAt;
        $this->expiresAt = $expiresAt;
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?AccessEntity
    {
        return $this->user;
    }

    public function getPurpose(): AccessPasskeyCeremonyPurpose
    {
        return $this->purpose;
    }

    public function getChallengeHash(): string
    {
        return $this->challengeHash;
    }

    public function isUsable(
        string $plainChallenge,
        AccessPasskeyCeremonyPurpose $purpose,
        string $relyingPartyId,
        string $origin,
        \DateTimeImmutable $now,
    ): bool {
        return null === $this->consumedAt
            && $this->expiresAt > $now
            && $this->purpose === $purpose
            && hash_equals($this->challengeHash, hash('sha256', $plainChallenge))
            && hash_equals($this->relyingPartyId, trim($relyingPartyId))
            && hash_equals($this->origin, rtrim(trim($origin), '/'));
    }

    public function consume(\DateTimeImmutable $consumedAt): void
    {
        if (null !== $this->consumedAt) {
            throw new \DomainException('Passkey challenge has already been consumed.');
        }

        if ($this->expiresAt <= $consumedAt) {
            throw new \DomainException('Passkey challenge has expired.');
        }

        $this->consumedAt = $consumedAt;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }
}
