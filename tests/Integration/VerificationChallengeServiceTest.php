<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Entity\Account;
use App\Accessing\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use App\Accessing\Tests\Support\DatabaseTestCase;

final class VerificationChallengeServiceTest extends DatabaseTestCase
{
    public function testEmailVerificationChallengeCanBeIssuedAndCompleted(): void
    {
        $entityManager = $this->refreshDatabase();
        $account = new Account('integration@accessing.local', 'Integration Account');
        /** @var AccessingCredentialServiceInterface $credentialService */
        $credentialService = static::getContainer()->get(AccessingCredentialServiceInterface::class);
        $credentialService->createCredential($account, 'integration-pass-123');
        $entityManager->persist($account);
        $entityManager->flush();

        /** @var AccessingVerificationChallengeServiceInterface $verificationChallengeService */
        $verificationChallengeService = static::getContainer()->get(AccessingVerificationChallengeServiceInterface::class);
        $issuedChallenge = $verificationChallengeService->issueEmailVerification($account, null);

        self::assertNotSame('', $issuedChallenge->plainCode);
        self::assertFalse($account->isEmailVerified());
        self::assertTrue($verificationChallengeService->completeEmailVerification($account, $issuedChallenge->plainCode));
        self::assertTrue($account->isEmailVerified());
    }
}
