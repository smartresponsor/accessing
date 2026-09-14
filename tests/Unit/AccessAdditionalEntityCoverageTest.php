<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessExternalIdentityEntity;
use App\Accessing\Entity\AccessMobilePendingAuthEntity;
use App\Accessing\Entity\AccessPasskeyChallengeEntity;
use App\Accessing\Entity\AccessPasskeyCredentialEntity;
use App\Accessing\Entity\AccessRecoveryCodeEntity;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use App\Accessing\ValueObject\AccessPasskeyCeremonyPurpose;
use PHPUnit\Framework\TestCase;

final class AccessAdditionalEntityCoverageTest extends TestCase
{
    public function testExternalIdentityNormalizesAndRefreshesProfile(): void
    {
        $user = new AccessEntity('owner@example.test');
        $identity = new AccessExternalIdentityEntity(
            $user,
            ' Google ',
            ' subject-1 ',
            ' Identity@Example.TEST ',
            false,
            '  Identity User  ',
            ' https://example.test/avatar.png ',
        );

        self::assertNull($identity->getId());
        self::assertSame($user, $identity->getUser());
        self::assertSame('identity@example.test', $identity->getEmail());
        self::assertFalse($identity->isEmailVerified());
        self::assertSame('Identity User', $identity->getDisplayName());
        self::assertSame('https://example.test/avatar.png', $identity->getAvatarUrl());
        self::assertInstanceOf(\DateTimeImmutable::class, $identity->getLastAuthenticatedAt());

        $authenticatedAt = new \DateTimeImmutable('+1 minute');
        $identity->recordAuthentication(' Updated@Example.TEST ', true, '   ', null, $authenticatedAt);
        self::assertSame('updated@example.test', $identity->getEmail());
        self::assertTrue($identity->isEmailVerified());
        self::assertNull($identity->getDisplayName());
        self::assertNull($identity->getAvatarUrl());
        self::assertSame($authenticatedAt, $identity->getLastAuthenticatedAt());

        $identity->recordAuthentication(
            ' final@example.test ',
            false,
            ' Final Identity ',
            ' https://example.test/final.png ',
        );
        self::assertSame('final@example.test', $identity->getEmail());
        self::assertFalse($identity->isEmailVerified());
        self::assertSame('Final Identity', $identity->getDisplayName());
        self::assertSame('https://example.test/final.png', $identity->getAvatarUrl());
    }

    public function testMobilePendingAuthenticationLifecycleAndValidation(): void
    {
        $user = new AccessEntity('pending@example.test');
        $now = new \DateTimeImmutable('2026-04-01T12:00:00+00:00');
        $expires = $now->modify('+10 minutes');
        $pending = new AccessMobilePendingAuthEntity(
            $user,
            ' pending-token ',
            AccessMobilePendingPurpose::EmailVerification,
            ' iPhone ',
            $now,
            $expires,
        );

        self::assertNull($pending->getId());
        self::assertSame($user, $pending->getUser());
        self::assertSame(AccessMobilePendingPurpose::EmailVerification, $pending->getPurpose());
        self::assertSame('iPhone', $pending->getDeviceName());
        self::assertSame($now, $pending->getCreatedAt());
        self::assertSame($expires, $pending->getExpiresAt());
        self::assertTrue($pending->hasToken('pending-token'));
        self::assertFalse($pending->hasToken('wrong-token'));
        self::assertTrue($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now));
        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::SecondFactor, $now));

        $pending->consume($now);
        self::assertFalse($pending->isUsable(AccessMobilePendingPurpose::EmailVerification, $now));

        try {
            $pending->consume($now);
            self::fail('Consumed pending authentication must not be reusable.');
        } catch (\DomainException $exception) {
            self::assertSame('Pending mobile authentication is unavailable.', $exception->getMessage());
        }

        $this->expectException(\InvalidArgumentException::class);
        new AccessMobilePendingAuthEntity(
            $user,
            'token',
            AccessMobilePendingPurpose::EmailVerification,
            'device',
            $now,
            $now,
        );
    }

    public function testPasskeyChallengeBindingConsumptionAndValidation(): void
    {
        $user = new AccessEntity('passkey-challenge@example.test');
        $created = new \DateTimeImmutable('2026-04-01T12:00:00+00:00');
        $expires = $created->modify('+5 minutes');
        $challenge = new AccessPasskeyChallengeEntity(
            'challenge-value',
            AccessPasskeyCeremonyPurpose::Registration,
            ' example.test ',
            ' https://example.test/ ',
            $created,
            $expires,
            $user,
        );

        self::assertNull($challenge->getId());
        self::assertSame($user, $challenge->getUser());
        self::assertSame(AccessPasskeyCeremonyPurpose::Registration, $challenge->getPurpose());
        self::assertSame(hash('sha256', 'challenge-value'), $challenge->getChallengeHash());
        self::assertSame($created, $challenge->getCreatedAt());
        self::assertSame($expires, $challenge->getExpiresAt());
        self::assertNull($challenge->getConsumedAt());
        self::assertTrue($challenge->isUsable(
            'challenge-value',
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test/',
            $created,
        ));
        self::assertFalse($challenge->isUsable(
            'wrong',
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test',
            $created,
        ));

        $consumedAt = $created->modify('+1 minute');
        $challenge->consume($consumedAt);
        self::assertSame($consumedAt, $challenge->getConsumedAt());
        self::assertFalse($challenge->isUsable(
            'challenge-value',
            AccessPasskeyCeremonyPurpose::Registration,
            'example.test',
            'https://example.test',
            $created,
        ));

        try {
            $challenge->consume($consumedAt);
            self::fail('A passkey challenge must be one-time use.');
        } catch (\DomainException $exception) {
            self::assertSame('Passkey challenge has already been consumed.', $exception->getMessage());
        }

        try {
            new AccessPasskeyChallengeEntity(
                'challenge',
                AccessPasskeyCeremonyPurpose::Registration,
                'example.test',
                'https://example.test',
                $created,
                $expires,
            );
            self::fail('Registration challenge without user must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Passkey registration challenge requires an access user.', $exception->getMessage());
        }
    }

    public function testPasskeyCredentialLifecycleCoversMaterialCounterRenameAndRevoke(): void
    {
        $user = new AccessEntity('credential@example.test');
        $credential = new AccessPasskeyCredentialEntity(
            $user,
            ' credential-id ',
            ' user-handle ',
            ' public-key ',
            [' internal ', 'usb', 'usb', ''],
            ' Primary Key ',
            1,
            ' record-v1 ',
        );

        self::assertNull($credential->getId());
        self::assertSame($user, $credential->getUser());
        self::assertSame('credential-id', $credential->getCredentialId());
        self::assertSame('user-handle', $credential->getUserHandle());
        self::assertSame('public-key', $credential->getPublicKey());
        self::assertSame(' record-v1 ', $credential->getCredentialRecord());
        self::assertSame(['internal', 'usb'], $credential->getTransports());
        self::assertSame(1, $credential->getSignCount());
        self::assertSame('Primary Key', $credential->getName());
        self::assertInstanceOf(\DateTimeImmutable::class, $credential->getCreatedAt());
        self::assertNull($credential->getLastUsedAt());
        self::assertTrue($credential->isActive());

        $credential->updateCredentialRecord('record-v2');
        self::assertSame('record-v2', $credential->getCredentialRecord());
        $credential->advanceSignCount(2);
        self::assertSame(2, $credential->getSignCount());
        self::assertInstanceOf(\DateTimeImmutable::class, $credential->getLastUsedAt());
        $credential->markUsedWithoutCounter();
        $credential->rename(' Renamed Key ');
        self::assertSame('Renamed Key', $credential->getName());
        $credential->revoke();
        $credential->revoke();
        self::assertFalse($credential->isActive());

        try {
            $credential->advanceSignCount(2);
            self::fail('Passkey counter regression must be rejected.');
        } catch (\DomainException $exception) {
            self::assertSame('Passkey sign count must increase monotonically.', $exception->getMessage());
        }

        try {
            $credential->updateCredentialRecord('   ');
            self::fail('Empty credential record must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame('Passkey credential record cannot be empty.', $exception->getMessage());
        }
    }

    public function testRecoveryCodeLifecycleUsesExplicitOrDerivedSuffix(): void
    {
        $user = new AccessEntity('recovery-code@example.test');
        $explicit = new AccessRecoveryCodeEntity($user, 'hash-value', 'ABCD');
        self::assertNull($explicit->getId());
        self::assertSame($user, $explicit->getUser());
        self::assertSame('hash-value', $explicit->getCodeHash());
        self::assertSame('ABCD', $explicit->getLastFourCharacters());
        self::assertFalse($explicit->isUsed());
        self::assertNull($explicit->getConsumedAt());
        self::assertInstanceOf(\DateTimeImmutable::class, $explicit->getCreatedAt());

        $usedAt = new \DateTimeImmutable('2026-04-01T12:00:00+00:00');
        $explicit->markUsed($usedAt);
        self::assertTrue($explicit->isUsed());
        self::assertSame($usedAt, $explicit->getConsumedAt());

        $derived = new AccessRecoveryCodeEntity($user, 'abcdef1234');
        self::assertSame('1234', $derived->getLastFourCharacters());
        $derived->setCodeHash(' changed5678 ');
        self::assertSame('changed5678', $derived->getCodeHash());
        self::assertSame('5678', $derived->getLastFourCharacters());

        $empty = new AccessRecoveryCodeEntity();
        self::assertNull($empty->getUser());
        self::assertSame('', $empty->getCodeHash());
        self::assertSame('', $empty->getLastFourCharacters());
        $empty->consume();
        self::assertTrue($empty->isUsed());
        self::assertInstanceOf(\DateTimeImmutable::class, $empty->getConsumedAt());
    }
}
