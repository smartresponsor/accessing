<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessExternalIdentityRepository;
use App\Objecting\EntityInterface\ObjectAuditedInterface;
use App\Objecting\EntityInterface\ObjectIdentifiedInterface;
use App\Objecting\EntityInterface\ObjectSourcedInterface;
use App\Objecting\EntityTrait\Embeddable\ObjectAuditEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectIdentityEmbeddableTrait;
use App\Objecting\EntityTrait\Embeddable\ObjectSourceEmbeddableTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessExternalIdentityRepository::class)]
#[ORM\Table(name: 'access_external_identity')]
#[ORM\UniqueConstraint(name: 'uniq_access_external_identity_provider_subject', columns: ['object_provider', 'object_external_id'])]
#[ORM\Index(name: 'idx_access_external_identity_user', columns: ['user_id'])]
final class AccessExternalIdentityEntity implements ObjectIdentifiedInterface, ObjectAuditedInterface, ObjectSourcedInterface
{
    use ObjectIdentityEmbeddableTrait;
    use ObjectAuditEmbeddableTrait;
    use ObjectSourceEmbeddableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AccessEntity::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private AccessEntity $user;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column]
    private bool $emailVerified;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $displayName;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $avatarUrl;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $lastAuthenticatedAt;

    public function __construct(
        AccessEntity $user,
        string $provider,
        string $subject,
        string $email,
        bool $emailVerified,
        ?string $displayName = null,
        ?string $avatarUrl = null,
    ) {
        $this->initializeObjectIdentity();
        $this->initializeObjectAudit();
        $this->initializeObjectSource('oauth2', mb_strtolower(trim($provider)), trim($subject), 'external_identity');
        $this->user = $user;
        $this->email = mb_strtolower(trim($email));
        $this->emailVerified = $emailVerified;
        $this->displayName = self::nullableTrim($displayName);
        $this->avatarUrl = self::nullableTrim($avatarUrl);
        $this->lastAuthenticatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): AccessEntity
    {
        return $this->user;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function getLastAuthenticatedAt(): \DateTimeImmutable
    {
        return $this->lastAuthenticatedAt;
    }

    public function recordAuthentication(
        string $email,
        bool $emailVerified,
        ?string $displayName,
        ?string $avatarUrl,
        ?\DateTimeImmutable $authenticatedAt = null,
    ): void {
        $this->email = mb_strtolower(trim($email));
        $this->emailVerified = $emailVerified;
        $this->displayName = self::nullableTrim($displayName);
        $this->avatarUrl = self::nullableTrim($avatarUrl);
        $this->lastAuthenticatedAt = $authenticatedAt ?? new \DateTimeImmutable();
        $this->touchModified($this->lastAuthenticatedAt);
    }

    private static function nullableTrim(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }
}
