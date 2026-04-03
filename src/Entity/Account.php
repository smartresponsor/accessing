<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'account')]
#[ORM\UniqueConstraint(name: 'uniq_account_email_address', columns: ['email_address'])]
final class Account implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $emailAddress;

    #[ORM\Column(length: 120)]
    private string $displayName;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $phoneNumber = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_ACCOUNT'];

    #[ORM\Column]
    private bool $emailVerified = false;

    #[ORM\Column]
    private bool $phoneVerified = false;

    #[ORM\Column]
    private int $failedSignInCount = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lockedUntil = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSignInAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $registeredAt;

    #[ORM\OneToOne(mappedBy: 'account', targetEntity: Credential::class, cascade: ['persist', 'remove'])]
    private ?Credential $credential = null;

    #[ORM\OneToOne(mappedBy: 'account', targetEntity: SecondFactor::class, cascade: ['persist', 'remove'])]
    private ?SecondFactor $secondFactor = null;

    /** @var Collection<int, VerificationChallenge> */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: VerificationChallenge::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['requestedAt' => 'DESC'])]
    private Collection $verificationChallenges;

    /** @var Collection<int, RecoveryCode> */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: RecoveryCode::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $recoveryCodes;

    /** @var Collection<int, SecurityEvent> */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: SecurityEvent::class, cascade: ['persist'])]
    #[ORM\OrderBy(['occurredAt' => 'DESC'])]
    private Collection $securityEvents;

    /** @var Collection<int, AccountSession> */
    #[ORM\OneToMany(mappedBy: 'account', targetEntity: AccountSession::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['lastSeenAt' => 'DESC'])]
    private Collection $accountSessions;

    public function __construct(string $emailAddress, string $displayName)
    {
        $this->emailAddress = $emailAddress;
        $this->displayName = $displayName;
        $this->registeredAt = new \DateTimeImmutable();
        $this->verificationChallenges = new ArrayCollection();
        $this->recoveryCodes = new ArrayCollection();
        $this->securityEvents = new ArrayCollection();
        $this->accountSessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function changeEmailAddress(string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
        $this->emailVerified = false;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function changeDisplayName(string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function changePhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
        $this->phoneVerified = false;
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerified;
    }

    public function markEmailVerified(): void
    {
        $this->emailVerified = true;
    }

    public function isPhoneVerified(): bool
    {
        return $this->phoneVerified;
    }

    public function markPhoneVerified(): void
    {
        $this->phoneVerified = true;
    }

    public function getFailedSignInCount(): int
    {
        return $this->failedSignInCount;
    }

    public function registerFailedSignInAttempt(): void
    {
        ++$this->failedSignInCount;
    }

    public function clearFailedSignInAttempts(): void
    {
        $this->failedSignInCount = 0;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function lockUntil(\DateTimeImmutable $lockedUntil): void
    {
        $this->lockedUntil = $lockedUntil;
    }

    public function unlock(): void
    {
        $this->lockedUntil = null;
        $this->failedSignInCount = 0;
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil instanceof \DateTimeImmutable && $this->lockedUntil > new \DateTimeImmutable();
    }

    public function getLastSignInAt(): ?\DateTimeImmutable
    {
        return $this->lastSignInAt;
    }

    public function markSuccessfulSignIn(): void
    {
        $this->lastSignInAt = new \DateTimeImmutable();
        $this->failedSignInCount = 0;
    }

    public function getRegisteredAt(): \DateTimeImmutable
    {
        return $this->registeredAt;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_ACCOUNT';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = array_values(array_unique($roles));
    }

    public function getCredential(): ?Credential
    {
        return $this->credential;
    }

    public function setCredential(Credential $credential): void
    {
        $this->credential = $credential;

        if ($credential->getAccount() !== $this) {
            $credential->setAccount($this);
        }
    }

    public function getSecondFactor(): ?SecondFactor
    {
        return $this->secondFactor;
    }

    public function setSecondFactor(?SecondFactor $secondFactor): void
    {
        $this->secondFactor = $secondFactor;

        if ($secondFactor instanceof SecondFactor && $secondFactor->getAccount() !== $this) {
            $secondFactor->setAccount($this);
        }
    }

    /**
     * @return Collection<int, VerificationChallenge>
     */
    public function getVerificationChallenges(): Collection
    {
        return $this->verificationChallenges;
    }

    public function addVerificationChallenge(VerificationChallenge $verificationChallenge): void
    {
        if (!$this->verificationChallenges->contains($verificationChallenge)) {
            $this->verificationChallenges->add($verificationChallenge);
            $verificationChallenge->setAccount($this);
        }
    }

    /**
     * @return Collection<int, RecoveryCode>
     */
    public function getRecoveryCodes(): Collection
    {
        return $this->recoveryCodes;
    }

    public function addRecoveryCode(RecoveryCode $recoveryCode): void
    {
        if (!$this->recoveryCodes->contains($recoveryCode)) {
            $this->recoveryCodes->add($recoveryCode);
            $recoveryCode->setAccount($this);
        }
    }

    /**
     * @return Collection<int, SecurityEvent>
     */
    public function getSecurityEvents(): Collection
    {
        return $this->securityEvents;
    }

    public function addSecurityEvent(SecurityEvent $securityEvent): void
    {
        if (!$this->securityEvents->contains($securityEvent)) {
            $this->securityEvents->add($securityEvent);
            $securityEvent->setAccount($this);
        }
    }

    /**
     * @return Collection<int, AccountSession>
     */
    public function getAccountSessions(): Collection
    {
        return $this->accountSessions;
    }

    public function addAccountSession(AccountSession $accountSession): void
    {
        if (!$this->accountSessions->contains($accountSession)) {
            $this->accountSessions->add($accountSession);
            $accountSession->setAccount($this);
        }
    }

    public function getUserIdentifier(): string
    {
        return $this->emailAddress;
    }

    public function getPassword(): string
    {
        return $this->credential?->getPasswordHash() ?? '';
    }

    public function eraseCredentials(): void {}
}
