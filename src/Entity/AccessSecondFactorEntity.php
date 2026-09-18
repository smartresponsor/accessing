<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessSecondFactorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessSecondFactorRepository::class)]
#[ORM\Table(name: 'access_second_factor')]
/**
 * Defines the second factor entity type and its canonical responsibility within the Accessing component.
 */
final class AccessSecondFactorEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'secondFactor')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AccessEntity $user;

    #[ORM\Column(length: 128)]
    private string $secret;

    #[ORM\Column(length: 180)]
    private string $label;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(AccessEntity $user, string $secret, string $label)
    {
        $this->user = $user;
        $this->secret = $secret;
        $this->label = $label;
        $this->createdAt = new \DateTimeImmutable();
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

        if ($user->getSecondFactor() !== $this) {
            $user->setSecondFactor($this);
        }
    }

    /**
     * Executes the get secret operation within the canonical Accessing component workflow.
     */
    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Executes the get label operation within the canonical Accessing component workflow.
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Executes the get created at operation within the canonical Accessing component workflow.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Executes the get confirmed at operation within the canonical Accessing component workflow.
     */
    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    /**
     * Executes the confirm operation within the canonical Accessing component workflow.
     */
    public function confirm(): void
    {
        $this->confirmedAt = new \DateTimeImmutable();
        $this->revokedAt = null;
    }

    /**
     * Executes the revoke operation within the canonical Accessing component workflow.
     */
    public function revoke(): void
    {
        $this->revokedAt = new \DateTimeImmutable();
    }

    /**
     * Executes the mark used operation within the canonical Accessing component workflow.
     */
    public function markUsed(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    /**
     * Executes the get last used at operation within the canonical Accessing component workflow.
     */
    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * Executes the is enabled operation within the canonical Accessing component workflow.
     */
    public function isEnabled(): bool
    {
        return $this->confirmedAt instanceof \DateTimeImmutable && null === $this->revokedAt;
    }
}
