<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'access_mobile_pending_auth')]
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): AccessEntity
    {
        return $this->user;
    }

    public function getPurpose(): AccessMobilePendingPurpose
    {
        return $this->purpose;
    }

    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function hasToken(string $plainToken): bool
    {
        return hash_equals($this->tokenHash, hash('sha256', self::required($plainToken)));
    }

    public function isUsable(AccessMobilePendingPurpose $purpose, \DateTimeImmutable $now): bool
    {
        return null === $this->consumedAt && $this->expiresAt > $now && $this->purpose === $purpose;
    }

    public function consume(\DateTimeImmutable $now): void
    {
        if (!$this->isUsable($this->purpose, $now)) {
            throw new \DomainException('Pending mobile authentication is unavailable.');
        } $this->consumedAt = $now;
    }

    private static function required(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new \InvalidArgumentException('Pending mobile authentication value cannot be empty.');
        }

        return $value;
    }
}
