<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'access_session')]
#[ORM\Index(name: 'idx_access_session_expires_at', columns: ['expires_at'])]
#[ORM\Index(name: 'idx_access_session_revoked_at', columns: ['revoked_at'])]
/**
 * Defines the session entity type and its canonical responsibility within the Accessing component.
 */
class AccessSessionEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessEntity::class, inversedBy: 'userSessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AccessEntity $user = null;

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
    public function __construct(?AccessEntity $user = null, ?string $sessionIdentifier = null, ?string $ipAddress = null, ?string $userAgent = null)
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->lastSeenAt = $now;
        $this->expiresAt = $now->modify('+30 days');

        if (null !== $user) {
            $this->setUser($user);
        }

        if (null !== $sessionIdentifier) {
            $this->setSessionIdentifier($sessionIdentifier);
        }

        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
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
     * Executes the get session identifier operation within the canonical Accessing component workflow.
     */
    public function getSessionIdentifier(): string
    {
        return $this->sessionIdentifier;
    }

    /**
     * Executes the set session identifier operation within the canonical Accessing component workflow.
     */
    public function setSessionIdentifier(string $sessionIdentifier): self
    {
        $this->sessionIdentifier = trim($sessionIdentifier);

        return $this;
    }

    /**
     * Executes the get ip address operation within the canonical Accessing component workflow.
     */
    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    /**
     * Executes the set ip address operation within the canonical Accessing component workflow.
     */
    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Executes the get user agent operation within the canonical Accessing component workflow.
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * Executes the set user agent operation within the canonical Accessing component workflow.
     */
    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Executes the is trusted operation within the canonical Accessing component workflow.
     */
    public function isTrusted(): bool
    {
        return $this->trusted;
    }

    /**
     * Executes the set trusted operation within the canonical Accessing component workflow.
     */
    public function setTrusted(bool $trusted): self
    {
        $this->trusted = $trusted;

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
     * Executes the get issued at operation within the canonical Accessing component workflow.
     */
    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Executes the get last seen at operation within the canonical Accessing component workflow.
     */
    public function getLastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    /**
     * Executes the touch operation within the canonical Accessing component workflow.
     */
    public function touch(?\DateTimeImmutable $lastSeenAt = null): self
    {
        $this->lastSeenAt = $lastSeenAt ?? new \DateTimeImmutable();

        return $this;
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
     * Executes the get revoked at operation within the canonical Accessing component workflow.
     */
    public function getRevokedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    /**
     * Executes the get invalidated at operation within the canonical Accessing component workflow.
     */
    public function getInvalidatedAt(): ?\DateTimeImmutable
    {
        return $this->revokedAt;
    }

    /**
     * Executes the is active operation within the canonical Accessing component workflow.
     */
    public function isActive(): bool
    {
        return null === $this->revokedAt && $this->expiresAt > new \DateTimeImmutable();
    }

    /**
     * Executes the revoke operation within the canonical Accessing component workflow.
     */
    public function revoke(?\DateTimeImmutable $revokedAt = null): self
    {
        $this->revokedAt = $revokedAt ?? new \DateTimeImmutable();

        return $this;
    }

    /**
     * Executes the invalidate operation within the canonical Accessing component workflow.
     */
    public function invalidate(?\DateTimeImmutable $invalidatedAt = null): self
    {
        return $this->revoke($invalidatedAt);
    }
}
