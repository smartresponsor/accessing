<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessPasskeyChallengeRepository;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessPasskeyChallengeRepository::class)]
#[ORM\Table(name: 'access_passkey_challenge')]
#[ORM\UniqueConstraint(name: 'uniq_access_passkey_challenge_hash', columns: ['challenge_hash'])]
#[ORM\Index(name: 'idx_access_passkey_challenge_user', columns: ['user_id'])]
/**
 * Defines the passkey challenge entity type and its canonical responsibility within the Accessing component.
 */
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

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
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

    /**
     * Executes the get id operation within the canonical Accessing component workflow.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Executes the get user operation within the canonical Accessing component workflow.
     */
    public function getUser(): ?AccessEntity
    {
        return $this->user;
    }

    /**
     * Executes the get purpose operation within the canonical Accessing component workflow.
     */
    public function getPurpose(): AccessPasskeyCeremonyPurpose
    {
        return $this->purpose;
    }

    /**
     * Executes the get challenge hash operation within the canonical Accessing component workflow.
     */
    public function getChallengeHash(): string
    {
        return $this->challengeHash;
    }

    /**
     * Executes the is usable operation within the canonical Accessing component workflow.
     */
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

    /**
     * Executes the consume operation within the canonical Accessing component workflow.
     */
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

    /**
     * Executes the get created at operation within the canonical Accessing component workflow.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Executes the get expires at operation within the canonical Accessing component workflow.
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Executes the get consumed at operation within the canonical Accessing component workflow.
     */
    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }
}
