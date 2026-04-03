<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccountSessionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccountSessionRepository::class)]
#[ORM\Table(name: 'account_session')]
#[ORM\UniqueConstraint(name: 'uniq_account_session_identifier', columns: ['session_identifier'])]
final class AccountSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'accountSessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(length: 128)]
    private string $sessionIdentifier;

    #[ORM\Column(nullable: true, length: 45)]
    private ?string $ipAddress = null;

    #[ORM\Column(nullable: true, length: 1000)]
    private ?string $userAgent = null;

    #[ORM\Column]
    private bool $trusted = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $lastSeenAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $invalidatedAt = null;

    public function __construct(Account $account, string $sessionIdentifier, ?string $ipAddress, ?string $userAgent)
    {
        $this->account = $account;
        $this->sessionIdentifier = $sessionIdentifier;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->createdAt = new \DateTimeImmutable();
        $this->lastSeenAt = new \DateTimeImmutable();
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

    public function getSessionIdentifier(): string
    {
        return $this->sessionIdentifier;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function isTrusted(): bool
    {
        return $this->trusted;
    }

    public function markTrusted(): void
    {
        $this->trusted = true;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    public function touch(): void
    {
        $this->lastSeenAt = new \DateTimeImmutable();
    }

    public function getInvalidatedAt(): ?\DateTimeImmutable
    {
        return $this->invalidatedAt;
    }

    public function invalidate(): void
    {
        $this->invalidatedAt = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->invalidatedAt === null;
    }
}
