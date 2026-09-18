<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessRecoveryCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessRecoveryCodeRepository::class)]
#[ORM\Table(name: 'access_recovery_code')]
#[ORM\Index(name: 'idx_access_recovery_code_consumed_at', columns: ['consumed_at'])]
/**
 * Defines the recovery code entity type and its canonical responsibility within the Accessing component.
 */
class AccessRecoveryCodeEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessEntity::class, inversedBy: 'recoveryCodes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AccessEntity $user = null;

    #[ORM\Column(name: 'code_hash', length: 255)]
    private string $codeHash = '';

    #[ORM\Column(name: 'consumed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    private ?string $lastFourCharacters;

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(?AccessEntity $user = null, ?string $codeHash = null, ?string $lastFourCharacters = null)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->lastFourCharacters = $lastFourCharacters;

        if (null !== $user) {
            $this->setUser($user);
        }

        if (null !== $codeHash) {
            $this->setCodeHash($codeHash);
        }
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
     * Executes the get code hash operation within the canonical Accessing component workflow.
     */
    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    /**
     * Executes the set code hash operation within the canonical Accessing component workflow.
     */
    public function setCodeHash(string $codeHash): self
    {
        $this->codeHash = trim($codeHash);

        return $this;
    }

    /**
     * Executes the get consumed at operation within the canonical Accessing component workflow.
     */
    public function getConsumedAt(): ?\DateTimeImmutable
    {
        return $this->consumedAt;
    }

    /**
     * Executes the is used operation within the canonical Accessing component workflow.
     */
    public function isUsed(): bool
    {
        return $this->consumedAt instanceof \DateTimeImmutable;
    }

    /**
     * Executes the consume operation within the canonical Accessing component workflow.
     */
    public function consume(?\DateTimeImmutable $consumedAt = null): self
    {
        $this->consumedAt = $consumedAt ?? new \DateTimeImmutable();

        return $this;
    }

    /**
     * Executes the mark used operation within the canonical Accessing component workflow.
     */
    public function markUsed(?\DateTimeImmutable $usedAt = null): self
    {
        return $this->consume($usedAt);
    }

    /**
     * Executes the get last four characters operation within the canonical Accessing component workflow.
     */
    public function getLastFourCharacters(): string
    {
        if (null === $this->lastFourCharacters) {
            return strtoupper(substr($this->codeHash, -4));
        }

        if ('' === $this->lastFourCharacters) {
            return strtoupper(substr($this->codeHash, -4));
        }

        return $this->lastFourCharacters;
    }

    /**
     * Executes the get created at operation within the canonical Accessing component workflow.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
