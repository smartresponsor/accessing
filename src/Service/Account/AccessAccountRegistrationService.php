<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Account;

use App\Accessing\Dto\AccessAccountRegistrationRequest;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\RepositoryInterface\AccessAccountRepositoryInterface;
use App\Accessing\ServiceInterface\Account\AccessAccountRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventRecorderInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class AccessAccountRegistrationService implements AccessAccountRegistrationServiceInterface
{
    public function __construct(
        private AccessAccountRepositoryInterface $accountRepository,
        private AccessCredentialServiceInterface $credentialService,
        private AccessVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessSecurityEventRecorderInterface $securityEventRecorder,
    ) {
    }

    public function register(AccessAccountRegistrationRequest $request): AccessAccountEntity
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
