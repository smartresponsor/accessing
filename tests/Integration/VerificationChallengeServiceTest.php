<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Account;
use App\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use App\Tests\Support\DatabaseTestCase;

final class VerificationChallengeServiceTest extends DatabaseTestCase
{
    public function testEmailVerificationChallengeCanBeIssuedAndCompleted(): void
    {
        $entityManager = $this->refreshDatabase();
        $account = new Account('integration@accessing.local', 'Integration Account');
        $credentialService = static::getContainer()->get(AccessingCredentialServiceInterface::class);
        $credentialService->createCredential($account, 'integration-pass-123');
        $entityManager->persist($account);
        $entityManager->flush();

        $verificationChallengeService = static::getContainer()->get(AccessingVerificationChallengeServiceInterface::class);
        $issuedChallenge = $verificationChallengeService->issueEmailVerification($account);

        self::assertNotSame('', $issuedChallenge->plainCode);
        self::assertFalse($account->isEmailVerified());
        self::assertTrue($verificationChallengeService->completeEmailVerification($account, $issuedChallenge->plainCode));
        self::assertTrue($account->isEmailVerified());
    }
}
