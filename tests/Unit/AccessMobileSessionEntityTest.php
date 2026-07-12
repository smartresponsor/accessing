<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessMobileSessionEntity;
use PHPUnit\Framework\TestCase;

final class AccessMobileSessionEntityTest extends TestCase
{
    public function testRotatesAndRevokesOpaqueTokens(): void
    {
        $now = new \DateTimeImmutable('2026-07-12T00:00:00+00:00');
        $session = new AccessMobileSessionEntity(
            new AccessEntity('mobile@example.test', 'Mobile'),
            'session-id',
            'access-one',
            'refresh-one',
            'iPhone',
            $now,
            $now->modify('+15 minutes'),
            $now->modify('+30 days'),
        );

        self::assertTrue($session->hasAccessToken('access-one'));
        self::assertTrue($session->hasRefreshToken('refresh-one'));
        self::assertFalse($session->hasAccessToken('access-two'));

        $session->rotate(
            'access-two',
            'refresh-two',
            $now->modify('+1 minute'),
            $now->modify('+16 minutes'),
            $now->modify('+30 days'),
        );

        self::assertFalse($session->hasRefreshToken('refresh-one'));
        self::assertTrue($session->hasRefreshToken('refresh-two'));
        self::assertTrue($session->isAccessActive($now->modify('+2 minutes')));

        $session->revoke($now->modify('+3 minutes'));

        self::assertFalse($session->isAccessActive($now->modify('+4 minutes')));
        self::assertFalse($session->isRefreshActive($now->modify('+4 minutes')));
    }
}
