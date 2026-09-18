<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessCredentialRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessCredentialRepository::class)]
#[ORM\Table(name: 'access_credential')]
/**
 * Defines the credential entity type and its canonical responsibility within the Accessing component.
 */
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

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(AccessEntity $user, string $passwordHash)
    {
        $this->user = $user;
        $this->passwordHash = $passwordHash;
        $this->passwordChangedAt = new \DateTimeImmutable();
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
    public function getUser(): AccessEntity
    {
        return $this->user;
    }

    /**
     * Executes the set user operation within the canonical Accessing component workflow.
     */
    public function setUser(AccessEntity $user): void
    {
        $this->user = $user;

        if ($user->getCredential() !== $this) {
            $user->setCredential($this);
        }
    }

    /**
     * Executes the get password hash operation within the canonical Accessing component workflow.
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * Executes the update password hash operation within the canonical Accessing component workflow.
     */
    public function updatePasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
        $this->passwordChangedAt = new \DateTimeImmutable();
    }

    /**
     * Executes the get password changed at operation within the canonical Accessing component workflow.
     */
    public function getPasswordChangedAt(): \DateTimeImmutable
    {
        return $this->passwordChangedAt;
    }
}
