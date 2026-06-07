<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\Entity\AccessUserSessionEntity;
use PHPUnit\Framework\TestCase;

final class AccessUserSessionEntityTest extends TestCase
{
    public function testIssuedAtMatchesCreatedAt(): void
    {
        $user = new AccessUserEntity('user@example.com');
        $session = new AccessUserSessionEntity($user, 'session-1');

        self::assertSame($session->getCreatedAt(), $session->getIssuedAt());
    }
}
