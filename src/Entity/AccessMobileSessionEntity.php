<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'access_mobile_session')]
/**
 * Defines the mobile session entity type and its canonical responsibility within the Accessing component.
 */
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

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
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
     * Executes the get session id operation within the canonical Accessing component workflow.
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Executes the get access expires at operation within the canonical Accessing component workflow.
     */
    public function getAccessExpiresAt(): \DateTimeImmutable
    {
        return $this->accessExpiresAt;
    }

    /**
     * Executes the get refresh expires at operation within the canonical Accessing component workflow.
     */
    public function getRefreshExpiresAt(): \DateTimeImmutable
    {
        return $this->refreshExpiresAt;
    }

    /**
     * Executes the has access token operation within the canonical Accessing component workflow.
     */
    public function hasAccessToken(string $token): bool
    {
        return hash_equals($this->accessTokenHash, self::hash($token));
    }

    /**
     * Executes the has refresh token operation within the canonical Accessing component workflow.
     */
    public function hasRefreshToken(string $token): bool
    {
        return hash_equals($this->refreshTokenHash, self::hash($token));
    }

    /**
     * Executes the has previous refresh token operation within the canonical Accessing component workflow.
     */
    public function hasPreviousRefreshToken(string $token): bool
    {
        return null !== $this->previousRefreshTokenHash && hash_equals($this->previousRefreshTokenHash, self::hash($token));
    }

    /**
     * Executes the is access active operation within the canonical Accessing component workflow.
     */
    public function isAccessActive(\DateTimeImmutable $now): bool
    {
        return null === $this->revokedAt && $this->accessExpiresAt > $now;
    }

    /**
     * Executes the is refresh active operation within the canonical Accessing component workflow.
     */
    public function isRefreshActive(\DateTimeImmutable $now): bool
    {
        return null === $this->revokedAt && null === $this->refreshReuseDetectedAt && $this->refreshExpiresAt > $now;
    }

    /**
     * Executes the rotate operation within the canonical Accessing component workflow.
     */
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

    /**
     * Executes the revoke operation within the canonical Accessing component workflow.
     */
    public function revoke(\DateTimeImmutable $now): void
    {
        $this->revokedAt ??= $now;
    }

    /**
     * Executes the mark refresh reuse detected operation within the canonical Accessing component workflow.
     */
    public function markRefreshReuseDetected(\DateTimeImmutable $now): void
    {
        $this->refreshReuseDetectedAt ??= $now;
        $this->revoke($now);
    }

    /**
     * Executes the hash operation within the canonical Accessing component workflow.
     */
    private static function hash(string $token): string
    {
        return hash('sha256', self::required($token));
    }

    /**
     * Executes the required operation within the canonical Accessing component workflow.
     */
    private static function required(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new \InvalidArgumentException('Mobile token value cannot be empty.');
        }

        return $value;
    }
}
