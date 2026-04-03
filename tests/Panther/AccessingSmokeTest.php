<?php

declare(strict_types=1);

namespace App\Tests\Panther;

use Symfony\Component\Panther\PantherTestCase;

final class AccessingSmokeTest extends PantherTestCase
{
    public function testSignInPageRenders(): void
    {
        $client = static::createPantherClient();
        $client->request('GET', '/sign-in');

        self::assertSelectorTextContains('h1', 'Sign in');
    }
}
