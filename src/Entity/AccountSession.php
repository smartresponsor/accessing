<?php

declare(strict_types=1);

namespace App\Entity;

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
    private ?string $ipAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private bool $trusted = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'last_seen_at')]
    private \DateTimeImmutable $lastSeenAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'expires_at')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, name: 'revoked_at')]
    private ?\DateTimeImmutable $revokedAt = null;

    public function __construct(?Account $account = null, ?string $sessionIdentifier = null, ?string $ipAddress = null, ?string $userAgent = null)
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->lastSeenAt = $now;
        $this->expiresAt = $now->modify('+30 days');

        if ($account !== null) {
            $this->setAccount($account);
        }

        if ($sessionIdentifier !== null) {
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
        return $this->revokedAt === null && $this->expiresAt > new \DateTimeImmutable();
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
