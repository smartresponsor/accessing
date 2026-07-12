<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Recovery;

use App\Accessing\Dto\AccessIssuedChallengeDto;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\ValueObject\AccessEmailAddress;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class AccessRecoveryService implements AccessRecoveryServiceInterface
{
    public function __construct(
        private AccessRepositoryInterface $userRepository,
        private AccessVerificationChallengeServiceInterface $verificationChallengeService,
        private AccessCredentialServiceInterface $credentialService,
        private AccessSecurityEventServiceInterface $securityEventService,
        private RateLimiterFactory $accessingRecoveryLimiter,
    ) {
    }

    public function requestPasswordRecovery(string $emailAddress, ?Request $request = null): ?AccessIssuedChallengeDto
    {
        $normalizedEmailAddress = new AccessEmailAddress($emailAddress);
        $limiterKey = sprintf('%s|%s', $normalizedEmailAddress, $request?->getClientIp() ?? 'unknown');

        if (!$this->accessingRecoveryLimiter->create($limiterKey)->consume()->isAccepted()) {
            $this->securityEventService->record(
                AccessSecurityEventType::RateLimitExceeded,
                AccessSecurityEventSeverity::Warning,
                null,
                $request,
                ['flow' => 'recovery_request'],
            );

            return null;
        }

        $user = $this->userRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if (null === $user) {
            return null;
        }

        return $this->verificationChallengeService->issuePasswordRecovery($user, $request);
    }

    public function resetPassword(string $emailAddress, string $code, string $newPassword): bool
    {
        $normalizedEmailAddress = new AccessEmailAddress($emailAddress);
        $user = $this->userRepository->findOneByEmailAddress($normalizedEmailAddress->toString());

        if (null === $user) {
            return false;
        }

        if (!$this->verificationChallengeService->consumePasswordRecovery($user, $code)) {
            return false;
        }

        $this->credentialService->changePassword($user, $newPassword);
        $user->unlock();
        $this->userRepository->save($user, true);

        $this->securityEventService->record(
            AccessSecurityEventType::RecoveryCompleted,
            AccessSecurityEventSeverity::Warning,
            $user,
        );

        return true;
    }
}
