<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Access;

use App\Accessing\Dto\AccessRegistrationRequest;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventRecorderInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final readonly class AccessRegistrationService implements AccessRegistrationServiceInterface
{
    public function __construct(
        private AccessRepositoryInterface $userRepository,
        private AccessCredentialServiceInterface $credentialService,
        private AccessVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessSecurityEventRecorderInterface $securityEventRecorder,
    ) {
    }

    public function register(AccessRegistrationRequest $request): AccessEntity
    {
        $user = new AccessEntity()
            ->setEmail($request->email)
            ->setDisplayName($request->displayName)
            ->setPhoneNumber($request->phoneNumber);

        if (null !== $this->userRepository->findOneByEmailAddress($user->getEmail())) {
            throw new \DomainException(sprintf('An user with email "%s" already exists.', $user->getEmail()));
        }

        $this->credentialService->createCredential($user, $request->plainPassword);

        try {
            $this->userRepository->save($user, true);
        } catch (UniqueConstraintViolationException $exception) {
            throw new \DomainException(sprintf('An user with email "%s" already exists.', $user->getEmail()), 0, $exception);
        }

        $challenge = $this->verificationChallengeService->issueEmailVerification($user);

        $this->securityEventRecorder->record('user.registered', $user, [
            'email' => $user->getEmail(),
            'challengeId' => $challenge->challenge->getId(),
        ]);

        return $user;
    }
}
