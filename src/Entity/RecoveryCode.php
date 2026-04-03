<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'recovery_code')]
final class RecoveryCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'recoveryCodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Account $account;

    #[ORM\Column(length: 64)]
    private string $codeHash;

    #[ORM\Column(length: 32)]
    private string $displayLabel;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    public function __construct(Account $account, string $codeHash, string $displayLabel)
    {
        $this->account = $account;
        $this->codeHash = $codeHash;
        $this->displayLabel = $displayLabel;
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getDisplayLabel(): string
    {
        return $this->displayLabel;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function markUsed(): void
    {
        $this->usedAt = new \DateTimeImmutable();
    }

    public function isUsed(): bool
    {
        return $this->usedAt instanceof \DateTimeImmutable;
    }
}
