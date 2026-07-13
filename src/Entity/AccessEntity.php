<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Entity;

use App\Accessing\Repository\AccessRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AccessRepository::class)]
#[ORM\Table(name: 'access')]
#[ORM\UniqueConstraint(name: 'uniq_access_email', columns: ['email'])]
class AccessEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    /** @var list<string> */
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

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lockedUntil = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSignInAt = null;

    #[ORM\OneToOne(targetEntity: AccessCredentialEntity::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?AccessCredentialEntity $credential = null;

    #[ORM\OneToOne(targetEntity: AccessSecondFactorEntity::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?AccessSecondFactorEntity $secondFactor = null;

    /** @var Collection<int, AccessRecoveryCodeEntity> */
    #[ORM\OneToMany(targetEntity: AccessRecoveryCodeEntity::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $recoveryCodes;

    /** @var Collection<int, AccessVerificationChallengeEntity> */
    #[ORM\OneToMany(targetEntity: AccessVerificationChallengeEntity::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $verificationChallenges;

    /** @var Collection<int, AccessSessionEntity> */
    #[ORM\OneToMany(targetEntity: AccessSessionEntity::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $userSessions;

    public function __construct(?string $email = null, ?string $displayName = null)
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->recoveryCodes = new ArrayCollection();
        $this->verificationChallenges = new ArrayCollection();
        $this->userSessions = new ArrayCollection();

        if (null !== $email) {
            $this->setEmail($email);
        }

        if (null !== $displayName) {
            $this->setDisplayName($displayName);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getEmailAddress(): string
    {
        return $this->getEmail();
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));
        $this->touch();

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return '' !== $this->email ? $this->email : 'user';
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));
        $this->touch();

        return $this;
    }

    public function getPassword(): string
    {
        return $this->credential?->getPasswordHash() ?? $this->passwordHash;
    }

    public function getPasswordHash(): string
    {
        return $this->getPassword();
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
        $this->displayName = null !== $displayName ? trim($displayName) : null;
        $this->touch();

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = null !== $phoneNumber ? trim($phoneNumber) : null;
        $this->touch();

        return $this;
    }

    public function changePhoneNumber(?string $phoneNumber): self
    {
        return $this->setPhoneNumber($phoneNumber);
    }

    public function getTotpSecret(): ?string
    {
        return $this->secondFactor?->getSecret() ?? $this->totpSecret;
    }

    public function setTotpSecret(?string $totpSecret): self
    {
        $this->totpSecret = null !== $totpSecret ? trim($totpSecret) : null;
        $this->touch();

        return $this;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        return $this->emailVerifiedAt;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt instanceof \DateTimeImmutable;
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

    public function isPhoneVerified(): bool
    {
        return $this->phoneVerifiedAt instanceof \DateTimeImmutable;
    }

    public function markPhoneVerified(?\DateTimeImmutable $verifiedAt = null): self
    {
        $this->phoneVerifiedAt = $verifiedAt ?? new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function isSecondFactorEnabled(): bool
    {
        return $this->secondFactor?->isEnabled() ?? $this->secondFactorEnabled;
    }

    public function setSecondFactorEnabled(bool $secondFactorEnabled): self
    {
        $this->secondFactorEnabled = $secondFactorEnabled;
        $this->touch();

        return $this;
    }

    public function getSecondFactor(): ?AccessSecondFactorEntity
    {
        return $this->secondFactor;
    }

    public function setSecondFactor(?AccessSecondFactorEntity $secondFactor): self
    {
        $this->secondFactor = $secondFactor;

        if (null !== $secondFactor && $secondFactor->getUser() !== $this) {
            $secondFactor->setUser($this);
        }

        $this->touch();

        return $this;
    }

    public function getCredential(): ?AccessCredentialEntity
    {
        return $this->credential;
    }

    public function setCredential(?AccessCredentialEntity $credential): self
    {
        $this->credential = $credential;

        if (null !== $credential && $credential->getUser() !== $this) {
            $credential->setUser($this);
        }

        $this->touch();

        return $this;
    }

    /** @return Collection<int, AccessRecoveryCodeEntity> */
    public function getRecoveryCodes(): Collection
    {
        return $this->recoveryCodes;
    }

    public function addRecoveryCode(AccessRecoveryCodeEntity $recoveryCode): self
    {
        if (!$this->recoveryCodes->contains($recoveryCode)) {
            $this->recoveryCodes->add($recoveryCode);
            $recoveryCode->setUser($this);
        }

        $this->touch();

        return $this;
    }

    /** @return Collection<int, AccessVerificationChallengeEntity> */
    public function getVerificationChallenges(): Collection
    {
        return $this->verificationChallenges;
    }

    public function addVerificationChallenge(AccessVerificationChallengeEntity $verificationChallenge): self
    {
        if (!$this->verificationChallenges->contains($verificationChallenge)) {
            $this->verificationChallenges->add($verificationChallenge);
            $verificationChallenge->setUser($this);
        }

        $this->touch();

        return $this;
    }

    /** @return Collection<int, AccessSessionEntity> */
    public function getUserSessions(): Collection
    {
        return $this->userSessions;
    }

    public function addUserSession(AccessSessionEntity $userSession): self
    {
        if (!$this->userSessions->contains($userSession)) {
            $this->userSessions->add($userSession);
            $userSession->setUser($this);
        }

        $this->touch();

        return $this;
    }

    public function isLocked(): bool
    {
        if ($this->lockedUntil instanceof \DateTimeImmutable && $this->lockedUntil <= new \DateTimeImmutable()) {
            $this->unlock();
        }

        return $this->locked;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function lock(): self
    {
        $this->locked = true;
        $this->touch();

        return $this;
    }

    public function lockUntil(\DateTimeImmutable $lockedUntil): self
    {
        $this->locked = true;
        $this->lockedUntil = $lockedUntil;
        $this->touch();

        return $this;
    }

    public function unlock(): self
    {
        $this->locked = false;
        $this->lockedUntil = null;
        $this->failedLoginCount = 0;
        $this->touch();

        return $this;
    }

    public function getFailedLoginCount(): int
    {
        return $this->failedLoginCount;
    }

    public function getFailedSignInCount(): int
    {
        return $this->failedLoginCount;
    }

    public function increaseFailedLoginCount(): self
    {
        ++$this->failedLoginCount;
        $this->touch();

        return $this;
    }

    public function registerFailedSignInAttempt(): self
    {
        return $this->increaseFailedLoginCount();
    }

    public function resetFailedLoginCount(): self
    {
        $this->failedLoginCount = 0;
        $this->touch();

        return $this;
    }

    public function markSuccessfulSignIn(): self
    {
        $this->failedLoginCount = 0;
        $this->locked = false;
        $this->lockedUntil = null;
        $this->lastSignInAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastSignInAt(): ?\DateTimeImmutable
    {
        return $this->lastSignInAt;
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
