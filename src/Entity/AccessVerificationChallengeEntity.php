<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\ValueObject\AccessVerificationChallengeType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'access_verification_challenge')]
#[ORM\Index(name: 'idx_access_verification_challenge_type', columns: ['channel_type'])]
#[ORM\Index(name: 'idx_access_verification_challenge_expires_at', columns: ['expires_at'])]
#[ORM\Index(name: 'idx_access_verification_challenge_user', columns: ['user_id'])]
/**
 * Defines the verification challenge entity type and its canonical responsibility within the Accessing component.
 */
class AccessVerificationChallengeEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessEntity::class, inversedBy: 'verificationChallenges')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AccessEntity $user = null;

    #[ORM\Column(name: 'channel_type', length: 32)]
    private string $channelType = '';

    #[ORM\Column(length: 255)]
    private string $token = '';

    #[ORM\Column(length: 255)]
    private string $target = '';

    #[ORM\Column]
    private bool $completed = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    private ?string $requestedIpAddress;

    #[ORM\Column(name: 'attempt_count')]
    private int $attemptCount = 0;

    /**
     * @throws \DateMalformedStringException
     */
    public function __construct(
        ?AccessEntity $user = null,
        AccessVerificationChallengeType|string|null $challengeType = null,
        ?string $target = null,
        ?string $token = null,
        ?\DateTimeImmutable $expiresAt = null,
        ?string $requestedIpAddress = null,
    ) {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->expiresAt = $expiresAt ?? $now->modify('+15 minutes');
        $this->requestedIpAddress = $requestedIpAddress;

        if (null !== $user) {
            $this->setUser($user);
        }

        if (null !== $challengeType) {
            $this->setChallengeType($challengeType);
        }

        if (null !== $target) {
            $this->setTarget($target);
        }

        if (null !== $token) {
            $this->setToken($token);
        }
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
     * Executes the set user operation within the canonical Accessing component workflow.
     */
    public function setUser(AccessEntity $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Executes the get challenge type operation within the canonical Accessing component workflow.
     */
    public function getChallengeType(): AccessVerificationChallengeType
    {
        return match ($this->channelType) {
            'email', 'email_verification' => AccessVerificationChallengeType::EmailVerification,
            'phone', 'phone_verification' => AccessVerificationChallengeType::PhoneVerification,
            default => AccessVerificationChallengeType::PasswordRecovery,
        };
    }

    /**
     * Executes the set challenge type operation within the canonical Accessing component workflow.
     */
    public function setChallengeType(AccessVerificationChallengeType|string $challengeType): self
    {
        $value = $challengeType instanceof AccessVerificationChallengeType ? $challengeType->value : trim($challengeType);
        $this->channelType = match ($value) {
            AccessVerificationChallengeType::EmailVerification->value, 'email' => 'email',
            AccessVerificationChallengeType::PhoneVerification->value, 'phone' => 'phone',
            AccessVerificationChallengeType::PasswordRecovery->value, 'recovery', 'password_recovery' => 'recovery',
            default => $value,
        };

        return $this;
    }

    /**
     * Executes the get channel type operation within the canonical Accessing component workflow.
     */
    public function getChannelType(): string
    {
        return $this->channelType;
    }

    /**
     * Executes the set channel type operation within the canonical Accessing component workflow.
     */
    public function setChannelType(string $channelType): self
    {
        return $this->setChallengeType($channelType);
    }

    /**
     * Executes the get token operation within the canonical Accessing component workflow.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Executes the set token operation within the canonical Accessing component workflow.
     */
    public function setToken(string $token): self
    {
        $this->token = trim($token);

        return $this;
    }

    /**
     * Executes the get code hash operation within the canonical Accessing component workflow.
     */
    public function getCodeHash(): string
    {
        return $this->token;
    }

    /**
     * Executes the get target operation within the canonical Accessing component workflow.
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Executes the set target operation within the canonical Accessing component workflow.
     */
    public function setTarget(string $target): self
    {
        $this->target = trim($target);

        return $this;
    }

    /**
     * Executes the is completed operation within the canonical Accessing component workflow.
     */
    public function isCompleted(): bool
    {
        return $this->completed;
    }

    /**
     * Executes the get consumed at operation within the canonical Accessing component workflow.
     */
    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    /**
     * Executes the consume operation within the canonical Accessing component workflow.
     */
    public function consume(?\DateTimeImmutable $consumedAt = null): self
    {
        return $this->markCompleted($consumedAt);
    }

    /**
     * Executes the mark completed operation within the canonical Accessing component workflow.
     */
    public function markCompleted(?\DateTimeImmutable $completedAt = null): self
    {
        $this->completed = true;
        $this->completedAt = $completedAt ?? new \DateTimeImmutable();

        return $this;
    }

    /**
     * Executes the get completed at operation within the canonical Accessing component workflow.
     */
    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    /**
     * Executes the get expires at operation within the canonical Accessing component workflow.
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Executes the set expires at operation within the canonical Accessing component workflow.
     */
    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Executes the get created at operation within the canonical Accessing component workflow.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Executes the get requested at operation within the canonical Accessing component workflow.
     */
    public function getRequestedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Executes the get requested ip address operation within the canonical Accessing component workflow.
     */
    public function getRequestedIpAddress(): ?string
    {
        return $this->requestedIpAddress;
    }

    /**
     * Executes the register attempt operation within the canonical Accessing component workflow.
     */
    public function registerAttempt(): self
    {
        ++$this->attemptCount;

        return $this;
    }

    /**
     * Executes the get attempt count operation within the canonical Accessing component workflow.
     */
    public function getAttemptCount(): int
    {
        return $this->attemptCount;
    }

    /**
     * Executes the has reached attempt limit operation within the canonical Accessing component workflow.
     */
    public function hasReachedAttemptLimit(int $maximumAttempts = 5): bool
    {
        return $this->attemptCount >= $maximumAttempts;
    }
}
