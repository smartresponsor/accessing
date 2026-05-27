<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Account;

use App\Accessing\Dto\AccountRegistrationRequest;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use App\Accessing\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class AccessingAccountRegistrationService implements AccessingAccountRegistrationServiceInterface
{
    public function __construct(
        private AccountRepositoryInterface $accountRepository,
        private AccessingCredentialServiceInterface $credentialService,
        private AccessingVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessingSecurityEventRecorderInterface $securityEventRecorder,
    ) {
    }

    public function register(AccountRegistrationRequest $request): AccessAccountEntity
    {
        $account = new AccessAccountEntity()
            ->setEmail($request->email)
            ->setDisplayName($request->displayName)
            ->setPhoneNumber($request->phoneNumber);

        if (null !== $this->accountRepository->findOneByEmailAddress($account->getEmail())) {
            throw new \DomainException(sprintf('An account with email "%s" already exists.', $account->getEmail()));
        }

        $this->credentialService->createCredential($account, $request->plainPassword);

        try {
            $this->accountRepository->save($account, true);
        } catch (UniqueConstraintViolationException $exception) {
            throw new \DomainException(sprintf('An account with email "%s" already exists.', $account->getEmail()), 0, $exception);
        }

        $challenge = $this->verificationChallengeService->issueEmailVerification($account);

        $this->securityEventRecorder->record('account.registered', $account, [
            'email' => $account->getEmail(),
            'challengeId' => $challenge->challenge->getId(),
        ]);

        return $account;
    }
}
