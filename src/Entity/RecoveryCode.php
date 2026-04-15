<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'accessing_recovery_code')]
#[ORM\Index(name: 'idx_accessing_recovery_code_consumed_at', columns: ['consumed_at'])]
class RecoveryCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'recoveryCodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Account $account = null;

    #[ORM\Column(name: 'code_hash', length: 255)]
    private string $codeHash = '';

    #[ORM\Column(name: 'consumed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    private ?string $lastFourCharacters;

    public function __construct(?Account $account = null, ?string $codeHash = null, ?string $lastFourCharacters = null)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastFourCharacters = $lastFourCharacters;

        if (null !== $account) {
            $this->setAccount($account);
        }

        if (null !== $codeHash) {
            $this->setCodeHash($codeHash);
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

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function setCodeHash(string $codeHash): self
    {
        $this->codeHash = trim($codeHash);

        return $this;
    }

    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }

    public function isUsed(): bool
    {
        return $this->consumedAt instanceof \DateTimeImmutable;
    }

    public function consume(?\DateTimeImmutable $consumedAt = null): self
    {
        $this->consumedAt = $consumedAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function markUsed(?\DateTimeImmutable $usedAt = null): self
    {
        return $this->consume($usedAt);
    }

    public function getLastFourCharacters(): string
    {
        if (null !== $this->lastFourCharacters && '' !== $this->lastFourCharacters) {
            return $this->lastFourCharacters;
        }

        return strtoupper(substr($this->codeHash, -4));
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
