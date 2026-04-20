<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingAccountSessionRoutesTest extends WebTestCase
{
    public function testSessionsRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sessions');

        self::assertResponseRedirects('/sign-in');
    }
}
