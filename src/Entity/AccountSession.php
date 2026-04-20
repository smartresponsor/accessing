<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accessing_account_session')]
#[ORM\Index(name: 'idx_accessing_account_session_expires_at', columns: ['expires_at'])]
#[ORM\Index(name: 'idx_accessing_account_session_revoked_at', columns: ['revoked_at'])]
class AccountSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'accountSessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Account $account = null;

    #[ORM\Column(length: 128, unique: true)]
    private string $sessionIdentifier = '';

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent;

    #[ORM\Column]
    private bool $trusted = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_seen_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $lastSeenAt;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(name: 'revoked_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    /**
     * @throws \DateMalformedStringException
     */
    public function __construct(?Account $account = null, ?string $sessionIdentifier = null, ?string $ipAddress = null, ?string $userAgent = null)
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->lastSeenAt = $now;
        $this->expiresAt = $now->modify('+30 days');

        if (null !== $account) {
            $this->setAccount($account);
        }

        if (null !== $sessionIdentifier) {
            $this->setSessionIdentifier($sessionIdentifier);
        }

        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
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

    public function getSessionIdentifier(): string
    {
        return $this->sessionIdentifier;
    }

    public function setSessionIdentifier(string $sessionIdentifier): self
    {
        $this->sessionIdentifier = trim($sessionIdentifier);

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function isTrusted(): bool
    {
        return $this->trusted;
    }

    public function setTrusted(bool $trusted): self
    {
        $this->trusted = $trusted;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function touch(?\DateTimeImmutable $lastSeenAt = null): self
    {
        $this->lastSeenAt = $lastSeenAt ?? new \DateTimeImmutable();

        return $this;
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

    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function getInvalidatedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    public function isActive(): bool
    {
        return null === $this->revokedAt && $this->expiresAt > new \DateTimeImmutable();
    }

    public function revoke(?\DateTimeImmutable $revokedAt = null): self
    {
        $this->revokedAt = $revokedAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function invalidate(?\DateTimeImmutable $invalidatedAt = null): self
    {
        return $this->revoke($invalidatedAt);
    }
}
