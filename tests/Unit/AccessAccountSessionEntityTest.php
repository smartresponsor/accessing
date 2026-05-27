<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Entity\AccessAccountSessionEntity;
use PHPUnit\Framework\TestCase;

final class AccessAccountSessionEntityTest extends TestCase
{
    public function testIssuedAtMatchesCreatedAt(): void
    {
        $account = new AccessAccountEntity('user@example.com');
        $session = new AccessAccountSessionEntity($account, 'session-1');

        self::assertSame($session->getCreatedAt(), $session->getIssuedAt());
    }
}
