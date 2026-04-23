<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'credential')]
final class AccessCredentialEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'credential')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AccessAccountEntity $account;

    #[ORM\Column(length: 255)]
    private string $passwordHash;

    #[ORM\Column]
    private \DateTimeImmutable $passwordChangedAt;

    public function __construct(AccessAccountEntity $account, string $passwordHash)
    {
        $this->account = $account;
        $this->passwordHash = $passwordHash;
        $this->passwordChangedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccount(): AccessAccountEntity
    {
        return $this->account;
    }

    public function setAccount(AccessAccountEntity $account): void
    {
        $this->account = $account;

        if ($account->getCredential() !== $this) {
            $account->setCredential($this);
        }
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function updatePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->passwordChangedAt = new \DateTimeImmutable();
    }

    public function getPasswordChangedAt(): \DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }
}

class_alias(AccessCredentialEntity::class, Credential::class);
