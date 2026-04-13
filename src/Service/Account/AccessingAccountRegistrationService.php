<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Dto\AccountRegistrationRequest;
use App\Entity\Account;
use App\RepositoryInterface\AccountRepositoryInterface;
use App\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use App\ServiceInterface\Verification\AccessingEmailVerificationServiceInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccessingAccountRegistrationService implements AccessingAccountRegistrationServiceInterface
{
    public function __construct(
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly AccessingEmailVerificationServiceInterface $emailVerificationService,
        private readonly AccessingSecurityEventRecorderInterface $securityEventRecorder,
    ) {
    }

    public function register(AccountRegistrationRequest $request): Account
    {
        $account = (new Account())
            ->setEmail($request->email)
            ->setDisplayName($request->displayName)
            ->setPhoneNumber($request->phoneNumber);

        $account->setPasswordHash($this->userPasswordHasher->hashPassword($account, $request->plainPassword));

        $this->accountRepository->save($account, true);

        $challenge = $this->emailVerificationService->issueChallenge($account);

        $this->securityEventRecorder->record('account.registered', $account, [
            'email' => $account->getEmail(),
            'challengeId' => $challenge->getId(),
        ]);

        return $account;
    }
}
