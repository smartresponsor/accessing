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
#[ORM\UniqueConstraint(name: 'uniq_access_external_identity_provider_subject', columns: ['provider', 'external_id'])]
#[ORM\Index(name: 'idx_access_external_identity_user', columns: ['user_id'])]
/**
 * Defines the external identity entity type and its canonical responsibility within the Accessing component.
 */
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

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
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
     * Executes the get email operation within the canonical Accessing component workflow.
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Executes the is email verified operation within the canonical Accessing component workflow.
     */
    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    /**
     * Executes the get display name operation within the canonical Accessing component workflow.
     */
    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    /**
     * Executes the get avatar url operation within the canonical Accessing component workflow.
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    /**
     * Executes the get last authenticated at operation within the canonical Accessing component workflow.
     */
    public function getLastAuthenticatedAt(): \DateTimeImmutable
    {
        return $this->lastAuthenticatedAt;
    }

    /**
     * Executes the record authentication operation within the canonical Accessing component workflow.
     */
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

    /**
     * Executes the nullable trim operation within the canonical Accessing component workflow.
     */
    private static function nullableTrim(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        return $value;
    }
}
