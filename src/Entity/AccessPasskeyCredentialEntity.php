<?php

declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessPasskeyCredentialRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AccessPasskeyCredentialRepository::class)]
#[ORM\Table(name: 'access_passkey_credential')]
#[ORM\UniqueConstraint(name: 'uniq_access_passkey_credential_id', columns: ['credential_id'])]
/**
 * Defines the passkey credential entity type and its canonical responsibility within the Accessing component.
 */
final class AccessPasskeyCredentialEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private AccessEntity $user;

    #[ORM\Column(name: 'credential_id', length: 1024)]
    private string $credentialId;

    #[ORM\Column(name: 'user_handle', length: 255)]
    private string $userHandle;

    #[ORM\Column(name: 'public_key', type: 'text')]
    private string $publicKey;

    #[ORM\Column(name: 'credential_record', type: 'text', nullable: true)]
    private ?string $credentialRecord = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $transports;

    #[ORM\Column]
    private int $signCount = 0;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $revokedAt = null;

    /** @param list<string> $transports */
    public function __construct(
        AccessEntity $user,
        string $credentialId,
        string $userHandle,
        string $publicKey,
        array $transports,
        string $name,
        int $signCount = 0,
        ?string $credentialRecord = null,
    ) {
        if ('' === trim($credentialId) || '' === trim($userHandle) || '' === trim($publicKey)) {
            throw new \InvalidArgumentException('Passkey credential material cannot be empty.');
        }

        if ($signCount < 0) {
            throw new \InvalidArgumentException('Passkey sign count cannot be negative.');
        }

        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Passkey name cannot be empty.');
        }

        $this->user = $user;
        $this->credentialId = trim($credentialId);
        $this->userHandle = trim($userHandle);
        $this->publicKey = trim($publicKey);
        $this->credentialRecord = null === $credentialRecord || '' === trim($credentialRecord) ? null : $credentialRecord;
        $this->transports = array_values(array_unique(array_filter(array_map('trim', $transports))));
        $this->name = mb_substr($name, 0, 120);
        $this->signCount = $signCount;
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
     * Executes the get credential id operation within the canonical Accessing component workflow.
     */
    public function getCredentialId(): string
    {
        return $this->credentialId;
    }

    /**
     * Executes the get user handle operation within the canonical Accessing component workflow.
     */
    public function getUserHandle(): string
    {
        return $this->userHandle;
    }

    /**
     * Executes the get public key operation within the canonical Accessing component workflow.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Executes the get credential record operation within the canonical Accessing component workflow.
     */
    public function getCredentialRecord(): ?string
    {
        return $this->credentialRecord;
    }

    /**
     * Executes the update credential record operation within the canonical Accessing component workflow.
     */
    public function updateCredentialRecord(string $credentialRecord): void
    {
        if ('' === trim($credentialRecord)) {
            throw new \InvalidArgumentException('Passkey credential record cannot be empty.');
        }

        $this->credentialRecord = $credentialRecord;
    }

    /** @return list<string> */
    public function getTransports(): array
    {
        return $this->transports;
    }

    /**
     * Executes the get sign count operation within the canonical Accessing component workflow.
     */
    public function getSignCount(): int
    {
        return $this->signCount;
    }

    /**
     * Executes the advance sign count operation within the canonical Accessing component workflow.
     */
    public function advanceSignCount(int $nextSignCount): void
    {
        if ($nextSignCount <= $this->signCount) {
            throw new \DomainException('Passkey sign count must increase monotonically.');
        }

        $this->signCount = $nextSignCount;
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    /**
     * Executes the mark used without counter operation within the canonical Accessing component workflow.
     */
    public function markUsedWithoutCounter(): void
    {
        $this->lastUsedAt = new \DateTimeImmutable();
    }

    /**
     * Executes the rename operation within the canonical Accessing component workflow.
     */
    public function rename(string $name): void
    {
        $name = trim($name);
        if ('' === $name) {
            throw new \InvalidArgumentException('Passkey name cannot be empty.');
        }

        $this->name = mb_substr($name, 0, 120);
    }

    /**
     * Executes the get name operation within the canonical Accessing component workflow.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Executes the get created at operation within the canonical Accessing component workflow.
     */
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Executes the get last used at operation within the canonical Accessing component workflow.
     */
    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    /**
     * Executes the revoke operation within the canonical Accessing component workflow.
     */
    public function revoke(): void
    {
        $this->revokedAt ??= new \DateTimeImmutable();
    }

    /**
     * Executes the is active operation within the canonical Accessing component workflow.
     */
    public function isActive(): bool
    {
        return null === $this->revokedAt;
    }
}
