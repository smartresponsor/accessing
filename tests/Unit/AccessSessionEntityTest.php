<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSessionEntity;
use PHPUnit\Framework\TestCase;

final class AccessSessionEntityTest extends TestCase
{
    public function testIssuedAtMatchesCreatedAt(): void
    {
        $user = new AccessEntity('user@example.com');
        $session = new AccessSessionEntity($user, 'session-1');

        self::assertSame($session->getCreatedAt(), $session->getIssuedAt());
    }
}
