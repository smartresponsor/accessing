<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfigurationInterface;
use Scheb\TwoFactorBundle\Model\Totp\TwoFactorInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accessing_account')]
#[ORM\UniqueConstraint(name: 'uniq_accessing_account_email', columns: ['email'])]
class Account implements UserInterface, PasswordAuthenticatedUserInterface, TwoFactorInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private string $passwordHash = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totpSecret = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $phoneVerifiedAt = null;

    #[ORM\Column]
    private bool $secondFactorEnabled = false;

    #[ORM\Column]
    private bool $locked = false;

    #[ORM\Column]
    private int $failedLoginCount = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));
        $this->touch();

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));
        $this->touch();

        return $this;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;
        $this->touch();

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName !== null ? trim($displayName) : null;
        $this->touch();

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber !== null ? trim($phoneNumber) : null;
        $this->touch();

        return $this;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = $totpSecret !== null ? trim($totpSecret) : null;
        $this->touch();

        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function markEmailVerified(?\DateTimeImmutable $verifiedAt = null): self
    {
        $this->emailVerifiedAt = $verifiedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function getPhoneVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->phoneVerifiedAt;
    }

    public function markPhoneVerified(?\DateTimeImmutable $verifiedAt = null): self
    {
        $this->phoneVerifiedAt = $verifiedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function isSecondFactorEnabled(): bool
    {
        return $this->secondFactorEnabled;
    }

    public function setSecondFactorEnabled(bool $secondFactorEnabled): self
    {
        $this->secondFactorEnabled = $secondFactorEnabled;
        $this->touch();

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return $this->secondFactorEnabled && null !== $this->totpSecret && '' !== $this->totpSecret;
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        return $this->isTotpAuthenticationEnabled()
            ? new TotpConfiguration($this->totpSecret, TotpConfiguration::ALGORITHM_SHA1, 30, 6)
            : null;
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    public function lock(): self
    {
        $this->locked = true;
        $this->touch();

        return $this;
    }

    public function unlock(): self
    {
        $this->locked = false;
        $this->failedLoginCount = 0;
        $this->touch();

        return $this;
    }

    public function getFailedLoginCount(): int
    {
        return $this->failedLoginCount;
    }

    public function increaseFailedLoginCount(): self
    {
        ++$this->failedLoginCount;
        $this->touch();

        return $this;
    }

    public function resetFailedLoginCount(): self
    {
        $this->failedLoginCount = 0;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
