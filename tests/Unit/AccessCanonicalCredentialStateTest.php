<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Entity\AccessCredentialEntity;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Entity\AccessSecondFactorEntity;
use PHPUnit\Framework\TestCase;

final class AccessCanonicalCredentialStateTest extends TestCase
{
    public function testCredentialEntityIsTheOnlyPasswordHashSource(): void
    {
        $user = new AccessEntity('credential@example.test', 'Credential');
        $credential = new AccessCredentialEntity($user, 'canonical-hash');
        $user->setCredential($credential);

        self::assertSame('canonical-hash', $user->getPassword());
        self::assertSame('canonical-hash', $user->getPasswordHash());
    }

    public function testSecondFactorEntityIsTheOnlyTotpStateSource(): void
    {
        $user = new AccessEntity('second-factor@example.test', 'Second Factor');
        $secondFactor = new AccessSecondFactorEntity($user, 'canonical-secret', $user->getEmailAddress());
        $secondFactor->confirm();
        $user->setSecondFactor($secondFactor);

        self::assertSame('canonical-secret', $user->getTotpSecret());
        self::assertTrue($user->isSecondFactorEnabled());
    }
}
