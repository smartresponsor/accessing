<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Entity;

use App\ValueObject\VerificationChallengeType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accessing_verification_challenge')]
#[ORM\Index(name: 'idx_accessing_verification_challenge_type', columns: ['channel_type'])]
#[ORM\Index(name: 'idx_accessing_verification_challenge_expires_at', columns: ['expires_at'])]
class VerificationChallenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'verificationChallenges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Account $account = null;

    #[ORM\Column(length: 32, name: 'channel_type')]
    private string $channelType = '';

    #[ORM\Column(length: 255)]
    private string $token = '';

    #[ORM\Column(length: 255)]
    private string $target = '';

    #[ORM\Column]
    private bool $completed = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'expires_at')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    private ?string $requestedIpAddress = null;
    private int $attemptCount = 0;

    public function __construct(
        ?Account $account = null,
        VerificationChallengeType|string|null $challengeType = null,
        ?string $target = null,
        ?string $token = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $requestedIpAddress = null,
    ) {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->expiresAt = $expiresAt ?? $now->modify('+15 minutes');
        $this->requestedIpAddress = $requestedIpAddress;

        if ($account !== null) {
            $this->setAccount($account);
        }

        if ($challengeType !== null) {
            $this->setChallengeType($challengeType);
        }

        if ($target !== null) {
            $this->setTarget($target);
        }

        if ($token !== null) {
            $this->setToken($token);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getChallengeType(): VerificationChallengeType
    {
        return match ($this->channelType) {
            'email', 'email_verification' => VerificationChallengeType::EmailVerification,
            'phone', 'phone_verification' => VerificationChallengeType::PhoneVerification,
            default => VerificationChallengeType::PasswordRecovery,
        };
    }

    public function setChallengeType(VerificationChallengeType|string $challengeType): self
    {
        $value = $challengeType instanceof VerificationChallengeType ? $challengeType->value : trim($challengeType);
        $this->channelType = match ($value) {
            VerificationChallengeType::EmailVerification->value, 'email' => 'email',
            VerificationChallengeType::PhoneVerification->value, 'phone' => 'phone',
            VerificationChallengeType::PasswordRecovery->value, 'recovery', 'password_recovery' => 'recovery',
            default => $value,
        };

        return $this;
    }

    public function getChannelType(): string
    {
        return $this->channelType;
    }

    public function setChannelType(string $channelType): self
    {
        return $this->setChallengeType($channelType);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = trim($token);

        return $this;
    }

    public function getCodeHash(): string
    {
        return $this->token;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function setTarget(string $target): self
    {
        $this->target = trim($target);

        return $this;
    }

    public function isCompleted(): bool
    {
        return $this->completed;
    }

    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function consume(?\DateTimeImmutable $consumedAt = null): self
    {
        return $this->markCompleted($consumedAt);
    }

    public function markCompleted(?\DateTimeImmutable $completedAt = null): self
    {
        $this->completed = true;
        $this->completedAt = $completedAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRequestedIpAddress(): ?string
    {
        return $this->requestedIpAddress;
    }

    public function registerAttempt(): self
    {
        ++$this->attemptCount;

        return $this;
    }
}
