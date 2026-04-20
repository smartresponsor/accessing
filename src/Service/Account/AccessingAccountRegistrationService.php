<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Account;

use App\Accessing\Dto\AccountRegistrationRequest;
use App\Accessing\Entity\Account;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use App\Accessing\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class AccessingAccountRegistrationService implements AccessingAccountRegistrationServiceInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private UserPasswordHasherInterface $userPasswordHasher,
        private AccessingVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessingSecurityEventRecorderInterface $securityEventRecorder,
    ) {
    }

    public function register(AccountRegistrationRequest $request): Account
    {
        $account = new Account()
            ->setEmail($request->email)
            ->setDisplayName($request->displayName)
            ->setPhoneNumber($request->phoneNumber);

        $account->setPasswordHash($this->userPasswordHasher->hashPassword($account, $request->plainPassword));

        $this->accountRepository->save($account, true);

        $challenge = $this->verificationChallengeService->issueEmailVerification($account);

        $this->securityEventRecorder->record('account.registered', $account, [
            'email' => $account->getEmail(),
            'challengeId' => $challenge->challenge->getId(),
        ]);

        return $account;
    }
}
