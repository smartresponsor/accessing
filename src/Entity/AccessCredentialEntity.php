<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessCredentialRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessCredentialRepository::class)]
#[ORM\Table(name: 'access_credential')]
final class AccessCredentialEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'credential')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AccessEntity $user;

    #[ORM\Column(length: 255)]
    private string $passwordHash;

    #[ORM\Column]
    private \DateTimeImmutable $passwordChangedAt;

    public function __construct(AccessEntity $user, string $passwordHash)
    {
        $this->user = $user;
        $this->passwordHash = $passwordHash;
        $this->passwordChangedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): AccessEntity
    {
        return $this->user;
    }

    public function setUser(AccessEntity $user): void
    {
        $this->user = $user;

        if ($user->getCredential() !== $this) {
            $user->setCredential($this);
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
