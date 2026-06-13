<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessSecondFactorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessSecondFactorRepository::class)]
#[ORM\Table(name: 'access_second_factor')]
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

    public function __construct(AccessEntity $user, string $secret, string $label)
    {
        $this->user = $user;
        $this->secret = $secret;
        $this->label = $label;
        $this->createdAt = new \DateTimeImmutable();
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

        if ($user->getSecondFactor() !== $this) {
            $user->setSecondFactor($this);
        }
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function confirm(): void
    {
        $this->confirmedAt = new \DateTimeImmutable();
        $this->revokedAt = null;
    }

    public function revoke(): void
    {
        $this->revokedAt = new \DateTimeImmutable();
    }

    public function markUsed(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function isEnabled(): bool
    {
        return $this->confirmedAt instanceof \DateTimeImmutable && null === $this->revokedAt;
    }
}
