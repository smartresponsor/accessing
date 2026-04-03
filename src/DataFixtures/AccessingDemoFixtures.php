<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\AccountSession;
use App\Repository\AccountRepository;
use App\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\ServiceInterface\SecondFactor\AccessingSecondFactorServiceInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use OTPHP\TOTP;

final class AccessingDemoFixtures extends Fixture
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly AccessingCredentialServiceInterface $credentialService,
        private readonly AccessingVerificationChallengeServiceInterface $verificationChallengeService,
        private readonly AccessingSecondFactorServiceInterface $secondFactorService,
        private readonly AccessingSecurityEventServiceInterface $securityEventService,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $support = $this->makeAccount('Support Operator', 'support@accessing.local', 'support-demo-pass');
        $support->setRoles(['ROLE_SUPPORT']);
        $support->markEmailVerified();
        $this->accountRepository->save($support);

        $verified = $this->makeAccount('Verified Account', 'verified@accessing.local', 'verified-demo-pass');
        $verified->markEmailVerified();
        $verified->changePhoneNumber('+13125550101');
        $verified->markPhoneVerified();
        $this->accountRepository->save($verified);

        $locked = $this->makeAccount('Locked Account', 'locked@accessing.local', 'locked-demo-pass');
        $locked->markEmailVerified();
        $locked->lockUntil(new \DateTimeImmutable('+10 minutes'));
        $locked->registerFailedSignInAttempt();
        $locked->registerFailedSignInAttempt();
        $locked->registerFailedSignInAttempt();
        $locked->registerFailedSignInAttempt();
        $locked->registerFailedSignInAttempt();
        $this->accountRepository->save($locked);

        $pending = $this->makeAccount('Pending Verification', 'pending@accessing.local', 'pending-demo-pass');
        $this->accountRepository->save($pending);

        $recovery = $this->makeAccount('Recovery Ready', 'recovery@accessing.local', 'recovery-demo-pass');
        $recovery->markEmailVerified();
        $this->accountRepository->save($recovery);

        $manager->flush();

        $this->verificationChallengeService->issueEmailVerification($pending);
        $this->verificationChallengeService->issuePhoneVerification($verified, '+13125550101');
        $this->verificationChallengeService->issuePasswordRecovery($recovery);

        $enrollment = $this->secondFactorService->beginEnrollment($verified);
        $totp = TOTP::create($enrollment->secret);
        $this->secondFactorService->confirmEnrollment($verified, $totp->now());

        $manager->persist(new AccountSession($verified, 'fixture-session-verified', '127.0.0.1', 'Fixture Browser'));
        $manager->persist(new AccountSession($support, 'fixture-session-support', '127.0.0.1', 'Fixture Browser'));
        $manager->flush();

        $this->securityEventService->record(SecurityEventType::AccountRegistered, SecurityEventSeverity::Info, $support);
        $this->securityEventService->record(SecurityEventType::AccountRegistered, SecurityEventSeverity::Info, $verified);
        $this->securityEventService->record(SecurityEventType::AccountLocked, SecurityEventSeverity::Critical, $locked);
        $this->securityEventService->record(SecurityEventType::RecoveryRequested, SecurityEventSeverity::Warning, $recovery);
    }

    private function makeAccount(string $displayName, string $emailAddress, string $plainPassword): Account
    {
        $account = new Account($emailAddress, $displayName);
        $this->credentialService->createCredential($account, $plainPassword);

        return $account;
    }
}
