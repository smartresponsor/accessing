<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Panther;

use PHPUnit\Framework\SkippedTestError;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Process\ExecutableFinder;

final class AccessingSmokeTest extends PantherTestCase
{
    public function testSignInPageRenders(): void
    {
        $enabled = getenv('ACCESSING_ENABLE_PANTHER');
        if (!is_string($enabled) || '1' !== $enabled) {
            throw new SkippedTestError('Panther suite is opt-in. Set ACCESSING_ENABLE_PANTHER=1 to enable it.');
        }

        if (null === (new ExecutableFinder())->find('geckodriver')) {
            throw new SkippedTestError('geckodriver is not installed in this environment.');
        }

        $client = static::createPantherClient();
        $client->request('GET', '/sign-in');

        self::assertSelectorTextContains('h1', 'Sign in');
    }
}
