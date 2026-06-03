<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;

final class AccessVerificationChallengeServiceTest extends AccessDatabaseTestCase
{
    public function testEmailVerificationChallengeCanBeIssuedAndCompleted(): void
    {
        $entityManager = $this->refreshDatabase();
        $account = new AccessAccountEntity('integration@accessing.local', 'Integration AccessAccountEntity');
        /** @var AccessCredentialServiceInterface $credentialService */
        $credentialService = static::getContainer()->get(AccessCredentialServiceInterface::class);
        $credentialService->createCredential($account, 'integration-pass-123');
        $entityManager->persist($account);
        $entityManager->flush();

        /** @var AccessVerificationChallengeServiceInterface $verificationChallengeService */
        $verificationChallengeService = static::getContainer()->get(AccessVerificationChallengeServiceInterface::class);
        $issuedChallenge = $verificationChallengeService->issueEmailVerification($account, null);

        self::assertNotSame('', $issuedChallenge->plainCode);
        self::assertFalse($account->isEmailVerified());
        self::assertTrue($verificationChallengeService->completeEmailVerification($account, $issuedChallenge->plainCode));
        self::assertTrue($account->isEmailVerified());
    }
}
