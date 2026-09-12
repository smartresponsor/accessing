<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Panther;

use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Process\ExecutableFinder;

final class AccessSmokeTest extends PantherTestCase
{
    public function testSignInPageRenders(): void
    {
        $enabled = getenv('ACCESSING_ENABLE_PANTHER');
        if (!is_string($enabled) || '1' !== $enabled) {
            self::markTestSkipped('Panther suite is opt-in. Set ACCESSING_ENABLE_PANTHER=1 to enable it.');
        }

        if (null === (new ExecutableFinder())->find('geckodriver')) {
            self::markTestSkipped('geckodriver is not installed in this environment.');
        }

        $client = static::createPantherClient();
        $client->request('GET', '/access/signin');

        self::assertSelectorTextContains('h1', 'Sign in');
    }
}
