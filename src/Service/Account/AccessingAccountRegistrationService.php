<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Entity\Account;
use App\Repository\AccountRepository;
use App\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventServiceInterface;
use App\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use App\ValueObject\EmailAddress;
use App\ValueObject\SecurityEventSeverity;
use App\ValueObject\SecurityEventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AccessingAccountRegistrationService implements AccessingAccountRegistrationServiceInterface
{
    public function __construct(
        private AccountRepository $accountRepository,
        private EntityManagerInterface $entityManager,
        private AccessingCredentialServiceInterface $credentialService,
        private AccessingVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessingSecurityEventServiceInterface $securityEventService,
    ) {}

    public function register(string $displayName, string $emailAddress, string $plainPassword, ?Request $request = null): array
    {
        $normalizedEmailAddress = new EmailAddress($emailAddress);

        if ($this->accountRepository->findOneByEmailAddress($normalizedEmailAddress->toString()) instanceof Account) {
            throw new \DomainException('An account with that email address already exists.');
        }

        $account = new Account($normalizedEmailAddress->toString(), trim($displayName));
        $account->setRoles(['ROLE_ACCOUNT']);
        $this->accountRepository->save($account);
        $this->credentialService->createCredential($account, $plainPassword);
        $this->entityManager->flush();

        $emailChallenge = $this->verificationChallengeService->issueEmailVerification($account, $request);

        $this->securityEventService->record(
            SecurityEventType::AccountRegistered,
            SecurityEventSeverity::Info,
            $account,
            $request,
        );

        return [
            'account' => $account,
            'emailChallenge' => $emailChallenge,
        ];
    }
}
