<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingSecurityEventRoutesTest extends WebTestCase
{
    public function testSecurityEventsRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/security-events');

        self::assertResponseRedirects('/sign-in');
    }
}
