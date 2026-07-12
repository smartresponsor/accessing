<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'access_mobile_session')]
final class AccessMobileSessionEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: AccessEntity::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AccessEntity $user;
    #[ORM\Column(length: 64, unique: true)]
    private string $sessionId;
    #[ORM\Column(length: 64, unique: true)]
    private string $accessTokenHash;
    #[ORM\Column(length: 64, unique: true)]
    private string $refreshTokenHash;
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $previousRefreshTokenHash = null;
    #[ORM\Column(length: 255)]
    private string $deviceName;
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'access_expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $accessExpiresAt;
    #[ORM\Column(name: 'refresh_expires_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $refreshExpiresAt;
    #[ORM\Column(name: 'revoked_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;
    #[ORM\Column(name: 'refresh_reuse_detected_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $refreshReuseDetectedAt = null;

    public function __construct(AccessEntity $user, string $sessionId, string $accessToken, string $refreshToken, string $deviceName, \DateTimeImmutable $now, \DateTimeImmutable $accessExpiresAt, \DateTimeImmutable $refreshExpiresAt)
    {
        if ($accessExpiresAt <= $now || $refreshExpiresAt <= $accessExpiresAt) {
            throw new \InvalidArgumentException('Mobile token expiry order is invalid.');
        }
        $this->user = $user;
        $this->sessionId = self::required($sessionId);
        $this->accessTokenHash = self::hash($accessToken);
        $this->refreshTokenHash = self::hash($refreshToken);
        $this->deviceName = self::required($deviceName);
        $this->createdAt = $now;
        $this->accessExpiresAt = $accessExpiresAt;
        $this->refreshExpiresAt = $refreshExpiresAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): AccessEntity
    {
        return $this->user;
    }

    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getAccessExpiresAt(): \DateTimeImmutable
    {
        return $this->accessExpiresAt;
    }

    public function getRefreshExpiresAt(): \DateTimeImmutable
    {
        return $this->refreshExpiresAt;
    }

    public function hasAccessToken(string $token): bool
    {
        return hash_equals($this->accessTokenHash, self::hash($token));
    }

    public function hasRefreshToken(string $token): bool
    {
        return hash_equals($this->refreshTokenHash, self::hash($token));
    }

    public function hasPreviousRefreshToken(string $token): bool
    {
        return null !== $this->previousRefreshTokenHash && hash_equals($this->previousRefreshTokenHash, self::hash($token));
    }

    public function isAccessActive(\DateTimeImmutable $now): bool
    {
        return null === $this->revokedAt && $this->accessExpiresAt > $now;
    }

    public function isRefreshActive(\DateTimeImmutable $now): bool
    {
        return null === $this->revokedAt && null === $this->refreshReuseDetectedAt && $this->refreshExpiresAt > $now;
    }

    public function rotate(string $accessToken, string $refreshToken, \DateTimeImmutable $now, \DateTimeImmutable $accessExpiresAt, \DateTimeImmutable $refreshExpiresAt): void
    {
        if (!$this->isRefreshActive($now)) {
            throw new \DomainException('Mobile refresh token is unavailable.');
        }
        $this->accessTokenHash = self::hash($accessToken);
        $this->previousRefreshTokenHash = $this->refreshTokenHash;
        $this->refreshTokenHash = self::hash($refreshToken);
        $this->accessExpiresAt = $accessExpiresAt;
        $this->refreshExpiresAt = $refreshExpiresAt;
    }

    public function revoke(\DateTimeImmutable $now): void
    {
        $this->revokedAt ??= $now;
    }

    public function markRefreshReuseDetected(\DateTimeImmutable $now): void
    {
        $this->refreshReuseDetectedAt ??= $now;
        $this->revoke($now);
    }

    private static function hash(string $token): string
    {
        return hash('sha256', self::required($token));
    }

    private static function required(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new \InvalidArgumentException('Mobile token value cannot be empty.');
        }

        return $value;
    }
}
