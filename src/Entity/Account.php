<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToOne(targetEntity: Credential::class, mappedBy: 'account', cascade: ['persist', 'remove'])]
    private ?Credential $credential = null;

    #[ORM\OneToOne(targetEntity: SecondFactor::class, mappedBy: 'account', cascade: ['persist', 'remove'])]
    private ?SecondFactor $secondFactor = null;

    /** @var Collection<int, RecoveryCode> */
    #[ORM\OneToMany(targetEntity: RecoveryCode::class, mappedBy: 'account', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $recoveryCodes;

    /** @var Collection<int, VerificationChallenge> */
    #[ORM\OneToMany(targetEntity: VerificationChallenge::class, mappedBy: 'account', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $verificationChallenges;

    /** @var Collection<int, AccountSession> */
    #[ORM\OneToMany(targetEntity: AccountSession::class, mappedBy: 'account', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $accountSessions;

    public function __construct(?string $email = null, ?string $displayName = null)
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->recoveryCodes = new ArrayCollection();
        $this->verificationChallenges = new ArrayCollection();
        $this->accountSessions = new ArrayCollection();

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
        return '' !== $this->email ? $this->email : 'account';
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_ACCOUNT';

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
        return $this->totpSecret;
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

    public function getSecondFactor(): ?SecondFactor
    {
        return $this->secondFactor;
    }

    public function setSecondFactor(?SecondFactor $secondFactor): self
    {
        $this->secondFactor = $secondFactor;
        $this->secondFactorEnabled = $secondFactor?->isEnabled() ?? false;

        if (null !== $secondFactor && $secondFactor->getAccount() !== $this) {
            $secondFactor->setAccount($this);
        }

        $this->touch();

        return $this;
    }

    public function isTotpAuthenticationEnabled(): bool
    {
        return ($this->secondFactor?->isEnabled() ?? false)
            || ($this->secondFactorEnabled && null !== $this->totpSecret && '' !== $this->totpSecret);
    }

    public function getTotpAuthenticationUsername(): string
    {
        return $this->email;
    }

    public function getTotpAuthenticationConfiguration(): ?TotpConfigurationInterface
    {
        $secret = $this->secondFactor?->getSecret() ?? $this->totpSecret;

        return $this->isTotpAuthenticationEnabled() && null !== $secret && '' !== $secret
            ? new TotpConfiguration($secret, TotpConfiguration::ALGORITHM_SHA1, 30, 6)
            : null;
    }

    public function getCredential(): ?Credential
    {
        return $this->credential;
    }

    public function setCredential(?Credential $credential): self
    {
        $this->credential = $credential;

        if (null !== $credential && $credential->getAccount() !== $this) {
            $credential->setAccount($this);
        }

        $this->touch();

        return $this;
    }

    /** @return Collection<int, RecoveryCode> */
    public function getRecoveryCodes(): Collection
    {
        return $this->recoveryCodes;
    }

    public function addRecoveryCode(RecoveryCode $recoveryCode): self
    {
        if (!$this->recoveryCodes->contains($recoveryCode)) {
            $this->recoveryCodes->add($recoveryCode);
            $recoveryCode->setAccount($this);
        }

        $this->touch();

        return $this;
    }

    /** @return Collection<int, VerificationChallenge> */
    public function getVerificationChallenges(): Collection
    {
        return $this->verificationChallenges;
    }

    public function addVerificationChallenge(VerificationChallenge $verificationChallenge): self
    {
        if (!$this->verificationChallenges->contains($verificationChallenge)) {
            $this->verificationChallenges->add($verificationChallenge);
            $verificationChallenge->setAccount($this);
        }

        $this->touch();

        return $this;
    }

    /** @return Collection<int, AccountSession> */
    public function getAccountSessions(): Collection
    {
        return $this->accountSessions;
    }

    public function addAccountSession(AccountSession $accountSession): self
    {
        if (!$this->accountSessions->contains($accountSession)) {
            $this->accountSessions->add($accountSession);
            $accountSession->setAccount($this);
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
