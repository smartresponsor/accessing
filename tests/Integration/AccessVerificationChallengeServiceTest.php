<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;

final class AccessVerificationChallengeServiceTest extends AccessDatabaseTestCase
{
    public function testEmailVerificationChallengeCanBeIssuedAndCompleted(): void
    {
        $entityManager = $this->refreshDatabase();
        $user = new AccessEntity('integration@accessing.local', 'Integration AccessEntity');
        /** @var AccessCredentialServiceInterface $credentialService */
        $credentialService = static::getContainer()->get(AccessCredentialServiceInterface::class);
        $credentialService->createCredential($user, 'integration-pass-123');
        $entityManager->persist($user);
        $entityManager->flush();

        /** @var AccessVerificationChallengeServiceInterface $verificationChallengeService */
        $verificationChallengeService = static::getContainer()->get(AccessVerificationChallengeServiceInterface::class);
        $issuedChallenge = $verificationChallengeService->issueEmailVerification($user, null);

        self::assertNotSame('', $issuedChallenge->plainCode);
        self::assertFalse($user->isEmailVerified());
        self::assertTrue($verificationChallengeService->completeEmailVerification($user, $issuedChallenge->plainCode));
        self::assertTrue($user->isEmailVerified());
    }

    public function testEmailVerificationChallengeBecomesTerminalAfterFiveFailedAttempts(): void
    {
        $entityManager = $this->refreshDatabase();
        $user = new AccessEntity('attempt-limit@accessing.local', 'Attempt Limit');
        /** @var AccessCredentialServiceInterface $credentialService */
        $credentialService = static::getContainer()->get(AccessCredentialServiceInterface::class);
        $credentialService->createCredential($user, 'integration-pass-123');
        $entityManager->persist($user);
        $entityManager->flush();

        /** @var AccessVerificationChallengeServiceInterface $verificationChallengeService */
        $verificationChallengeService = static::getContainer()->get(AccessVerificationChallengeServiceInterface::class);
        $issuedChallenge = $verificationChallengeService->issueEmailVerification($user, null);

        for ($attempt = 0; $attempt < 5; ++$attempt) {
            self::assertFalse($verificationChallengeService->completeEmailVerification($user, '000000'));
        }

        $entityManager->clear();
        $challenge = $entityManager->find($issuedChallenge->challenge::class, $issuedChallenge->challenge->getId());

        self::assertNotNull($challenge);
        self::assertSame(5, $challenge->getAttemptCount());
        self::assertTrue($challenge->isCompleted());
        self::assertFalse($verificationChallengeService->completeEmailVerification($user, $issuedChallenge->plainCode));
    }
}
