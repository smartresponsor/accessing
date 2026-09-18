<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'access_mobile_pending_auth')]
/**
 * Defines the mobile pending auth entity type and its canonical responsibility within the Accessing component.
 */
final class AccessMobilePendingAuthEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: AccessEntity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AccessEntity $user;
    #[ORM\Column(length: 64, unique: true)]
    private string $tokenHash;
    #[ORM\Column(enumType: AccessMobilePendingPurpose::class)]
    private AccessMobilePendingPurpose $purpose;
    #[ORM\Column(length: 255)]
    private string $deviceName;
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;
    #[ORM\Column(name: 'consumed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(AccessEntity $user, string $plainToken, AccessMobilePendingPurpose $purpose, string $deviceName, \DateTimeImmutable $now, \DateTimeImmutable $expiresAt)
    {
        if ($expiresAt <= $now) {
            throw new \InvalidArgumentException('Pending mobile authentication expiry must be in the future.');
        }
        $this->user = $user;
        $this->tokenHash = hash('sha256', self::required($plainToken));
        $this->purpose = $purpose;
        $this->deviceName = self::required($deviceName);
        $this->createdAt = $now;
        $this->expiresAt = $expiresAt;
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
     * Executes the get purpose operation within the canonical Accessing component workflow.
     */
    public function getPurpose(): AccessMobilePendingPurpose
    {
        return $this->purpose;
    }

    /**
     * Executes the get device name operation within the canonical Accessing component workflow.
     */
    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    /**
     * Executes the get created at operation within the canonical Accessing component workflow.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Executes the get expires at operation within the canonical Accessing component workflow.
     */
    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    /**
     * Executes the has token operation within the canonical Accessing component workflow.
     */
    public function hasToken(string $plainToken): bool
    {
        return hash_equals($this->tokenHash, hash('sha256', self::required($plainToken)));
    }

    /**
     * Executes the is usable operation within the canonical Accessing component workflow.
     */
    public function isUsable(AccessMobilePendingPurpose $purpose, \DateTimeImmutable $now): bool
    {
        return null === $this->consumedAt && $this->expiresAt > $now && $this->purpose === $purpose;
    }

    /**
     * Executes the consume operation within the canonical Accessing component workflow.
     */
    public function consume(\DateTimeImmutable $now): void
    {
        if (!$this->isUsable($this->purpose, $now)) {
            throw new \DomainException('Pending mobile authentication is unavailable.');
        } $this->consumedAt = $now;
    }

    /**
     * Executes the required operation within the canonical Accessing component workflow.
     */
    private static function required(string $value): string
    {
        $value = trim($value);
        if ('' !== $value) {
            return $value;
        }

        throw new \InvalidArgumentException('Pending mobile authentication value cannot be empty.');
    }
}
