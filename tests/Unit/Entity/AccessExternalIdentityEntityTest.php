<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit\Entity;

use App\Accessing\Dto\AccessExternalIdentityProfile;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessExternalIdentityEntity;
use PHPUnit\Framework\TestCase;

final class AccessExternalIdentityEntityTest extends TestCase
{
    public function testUsesObjectingSourceIdentityAndAuditPacks(): void
    {
        $user = new AccessEntity('owner@example.com', 'Owner');
        $identity = new AccessExternalIdentityEntity(
            $user,
            'Google',
            'google-subject',
            'Owner@Example.com',
            true,
            'Owner',
            'https://example.test/avatar.png',
        );

        self::assertSame($user, $identity->getUser());
        self::assertSame('oauth2', $identity->getObjectSource());
        self::assertSame('google', $identity->getObjectProvider());
        self::assertSame('google-subject', $identity->getObjectExternalId());
        self::assertSame('external_identity', $identity->getObjectSourceType());
        self::assertSame('owner@example.com', $identity->getEmail());
        self::assertTrue($identity->isEmailVerified());
        self::assertNull($identity->getModifiedAt());

        $authenticatedAt = new \DateTimeImmutable('+1 second');
        $identity->recordAuthentication('new@example.com', false, 'New Owner', null, $authenticatedAt);

        self::assertSame('new@example.com', $identity->getEmail());
        self::assertFalse($identity->isEmailVerified());
        self::assertSame($authenticatedAt, $identity->getLastAuthenticatedAt());
        self::assertSame($authenticatedAt, $identity->getModifiedAt());
    }

    public function testProfileRequiresProviderSubjectAndEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AccessExternalIdentityProfile('google', '', 'owner@example.com', true);
    }
}
