<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\VerificationChallengeRepository;
use App\ValueObject\VerificationChallengeType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerificationChallengeRepository::class)]
#[ORM\Table(name: 'verification_challenge')]
#[ORM\Index(name: 'idx_verification_challenge_type_expires', columns: ['challenge_type', 'expires_at'])]
final class VerificationChallenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'verificationChallenges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(enumType: VerificationChallengeType::class, length: 32)]
    private VerificationChallengeType $challengeType;

    #[ORM\Column(length: 180)]
    private string $destination;

    #[ORM\Column(length: 64)]
    private string $codeHash;

    #[ORM\Column(nullable: true, length: 45)]
    private ?string $requestedByIpAddress = null;

    #[ORM\Column]
    private \DateTimeImmutable $requestedAt;

    #[ORM\Column]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    #[ORM\Column]
    private int $attemptCount = 0;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    public function __construct(
        Account $account,
        VerificationChallengeType $challengeType,
        string $destination,
        string $codeHash,
        \DateTimeImmutable $expiresAt,
        ?string $requestedByIpAddress = null,
        array $metadata = [],
    ) {
        $this->account = $account;
        $this->challengeType = $challengeType;
        $this->destination = $destination;
        $this->codeHash = $codeHash;
        $this->expiresAt = $expiresAt;
        $this->requestedByIpAddress = $requestedByIpAddress;
        $this->requestedAt = new \DateTimeImmutable();
        $this->metadata = $metadata;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): void
    {
        $this->account = $account;
    }

    public function getChallengeType(): VerificationChallengeType
    {
        return $this->challengeType;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getRequestedByIpAddress(): ?string
    {
        return $this->requestedByIpAddress;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->requestedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function consume(): void
    {
        $this->consumedAt = new \DateTimeImmutable();
    }

    public function registerAttempt(): void
    {
        ++$this->attemptCount;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && $this->consumedAt === null;
    }

    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
