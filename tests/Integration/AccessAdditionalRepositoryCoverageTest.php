<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Integration;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessExternalIdentityEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\Entity\AccessMobileSessionEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\Entity\AccessResetPasswordRequestEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use App\Accessing\Repository\AccessCredentialRepository;
use App\Accessing\Repository\AccessExternalIdentityRepository;
use App\Accessing\Repository\AccessMobilePendingAuthRepository;
use App\Accessing\Repository\AccessMobileSessionRepository;
use App\Accessing\Repository\AccessPasskeyChallengeRepository;
use App\Accessing\Repository\AccessPasskeyCredentialRepository;
use App\Accessing\Repository\AccessRecoveryCodeRepository;
use App\Accessing\Repository\AccessRepository;
use App\Accessing\Repository\AccessResetPasswordRequestRepository;
use App\Accessing\Repository\AccessSecondFactorRepository;
use App\Accessing\Tests\Support\AccessDatabaseTestCase;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;

final class AccessAdditionalRepositoryCoverageTest extends AccessDatabaseTestCase
{
    public function testCredentialAndExternalIdentityRepositoriesRoundTrip(): void
    {
        $this->refreshDatabase();
        /** @var AccessRepository $users */
        $users = static::getContainer()->get(AccessRepository::class);
        /** @var AccessCredentialRepository $credentials */
        $credentials = static::getContainer()->get(AccessCredentialRepository::class);
        /** @var AccessExternalIdentityRepository $identities */
        $identities = static::getContainer()->get(AccessExternalIdentityRepository::class);

        $user = new AccessEntity('repository-auth@example.test', 'Repository Auth');
        $users->save($user, true);
        $credential = new AccessCredentialEntity($user, 'password-hash');
        $credentials->save($credential, true);

        self::assertSame($credential, $credentials->findOneForUser($user));
        self::assertNull($credentials->findOneForUser(new AccessEntity('missing-credential@example.test')));

        $identity = new AccessExternalIdentityEntity(
            $user,
            'Google',
            'subject-123',
            'repository-auth@example.test',
            true,
            'Repository Auth',
        );
        $identities->save($identity, true);
        self::assertSame($identity, $identities->findOneByProviderAndSubject(' GOOGLE ', 'subject-123'));
        self::assertNull($identities->findOneByProviderAndSubject('google', 'missing-subject'));
    }

    public function testMobilePendingAndSessionRepositoriesResolveHashedTokens(): void
    {
        $this->refreshDatabase();
        /** @var AccessRepository $users */
        $users = static::getContainer()->get(AccessRepository::class);
        /** @var AccessMobilePendingAuthRepository $pendingRepository */
        $pendingRepository = static::getContainer()->get(AccessMobilePendingAuthRepository::class);
        /** @var AccessMobileSessionRepository $sessionRepository */
        $sessionRepository = static::getContainer()->get(AccessMobileSessionRepository::class);

        $user = new AccessEntity('mobile-repository@example.test');
        $users->save($user, true);
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $pending = new AccessMobilePendingAuthEntity(
            $user,
            'pending-token',
            AccessMobilePendingPurpose::EmailVerification,
            'iPhone',
            $now,
            $now->modify('+10 minutes'),
        );
        $pendingRepository->save($pending, true);
        self::assertSame($pending, $pendingRepository->findOneByTokenHash(hash('sha256', 'pending-token')));
        self::assertNull($pendingRepository->findOneByTokenHash(hash('sha256', 'missing')));

        $session = new AccessMobileSessionEntity(
            $user,
            'mobile-session',
            'access-token',
            'refresh-token',
            'iPhone',
            $now,
            $now->modify('+15 minutes'),
            $now->modify('+1 hour'),
        );
        $sessionRepository->save($session, true);
        self::assertSame($session, $sessionRepository->findOneByAccessTokenHash(hash('sha256', 'access-token')));
        self::assertSame($session, $sessionRepository->findOneByRefreshTokenHash(hash('sha256', 'refresh-token')));
        self::assertNull($sessionRepository->findOneByPreviousRefreshTokenHash(hash('sha256', 'refresh-token')));

        $session->rotate(
            'next-access',
            'next-refresh',
            $now->modify('+1 minute'),
            $now->modify('+16 minutes'),
            $now->modify('+2 hours'),
        );
        $sessionRepository->save($session, true);
        self::assertSame($session, $sessionRepository->findOneByPreviousRefreshTokenHash(hash('sha256', 'refresh-token')));
    }

    public function testPasskeyRepositoriesFindChallengeAndFilterRevokedCredentials(): void
    {
        $this->refreshDatabase();
        /** @var AccessRepository $users */
        $users = static::getContainer()->get(AccessRepository::class);
        /** @var AccessPasskeyChallengeRepository $challenges */
        $challenges = static::getContainer()->get(AccessPasskeyChallengeRepository::class);
        /** @var AccessPasskeyCredentialRepository $credentials */
        $credentials = static::getContainer()->get(AccessPasskeyCredentialRepository::class);

        $user = new AccessEntity('passkey-repository@example.test');
        $users->save($user, true);
        $now = new \DateTimeImmutable('2026-09-16T12:00:00+00:00');
        $challenge = new AccessPasskeyChallengeEntity(
            'plain-challenge',
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test',
            $now,
            $now->modify('+5 minutes'),
            $user,
        );
        $challenges->save($challenge, true);
        self::assertSame($challenge, $challenges->findOneByChallengeHash(hash('sha256', 'plain-challenge')));
        self::assertNull($challenges->findOneByChallengeHash(hash('sha256', 'missing')));

        $active = new AccessPasskeyCredentialEntity($user, 'credential-active', 'handle-active', 'key-active', ['internal'], 'Active');
        $revoked = new AccessPasskeyCredentialEntity($user, 'credential-revoked', 'handle-revoked', 'key-revoked', [], 'Revoked');
        $revoked->revoke();
        $credentials->save($active);
        $credentials->save($revoked, true);

        self::assertSame($active, $credentials->findOneByCredentialId('credential-active'));
        self::assertNull($credentials->findOneByCredentialId('missing-credential'));
        self::assertSame([$active], $credentials->findActiveForUser($user));
    }

    public function testRecoverySecondFactorAndResetRepositoriesCoverLifecycleContracts(): void
    {
        $this->refreshDatabase();
        /** @var AccessRepository $users */
        $users = static::getContainer()->get(AccessRepository::class);
        /** @var AccessRecoveryCodeRepository $recoveryCodes */
        $recoveryCodes = static::getContainer()->get(AccessRecoveryCodeRepository::class);
        /** @var AccessSecondFactorRepository $secondFactors */
        $secondFactors = static::getContainer()->get(AccessSecondFactorRepository::class);
        /** @var AccessResetPasswordRequestRepository $resetRequests */
        $resetRequests = static::getContainer()->get(AccessResetPasswordRequestRepository::class);

        $user = new AccessEntity('recovery-repository@example.test');
        $users->save($user, true);
        $active = new AccessRecoveryCodeEntity($user, 'active-hash', '0001');
        $consumed = new AccessRecoveryCodeEntity($user, 'consumed-hash', '0002');
        $consumed->consume(new \DateTimeImmutable('-2 days'));
        $recoveryCodes->save($active);
        $recoveryCodes->save($consumed, true);
        self::assertSame([$active], $recoveryCodes->findActiveForUser($user));
        self::assertSame(1, $recoveryCodes->cleanupConsumedBefore(new \DateTimeImmutable('-1 day')));

        $secondFactor = new AccessSecondFactorEntity($user, 'secret', 'label');
        $secondFactors->save($secondFactor, true);
        self::assertNull($secondFactors->findEnabledForUser($user));
        $secondFactor->confirm();
        $secondFactors->save($secondFactor, true);
        self::assertSame($secondFactor, $secondFactors->findEnabledForUser($user));
        $secondFactor->revoke();
        $secondFactors->save($secondFactor, true);
        self::assertNull($secondFactors->findEnabledForUser($user));

        $reset = $resetRequests->createResetPasswordRequest(
            $user,
            new \DateTimeImmutable('+1 hour'),
            'selector',
            'hashed-token',
        );
        self::assertInstanceOf(AccessResetPasswordRequestEntity::class, $reset);
        self::assertSame($user, $reset->getUser());
        $resetRequests->save($reset, true);
        self::assertNotNull($reset->getId());

        $this->expectException(\InvalidArgumentException::class);
        $resetRequests->createResetPasswordRequest(new \stdClass(), new \DateTimeImmutable('+1 hour'), 'selector', 'token');
    }
}
