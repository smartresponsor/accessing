<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingRegistrationRoutesTest extends WebTestCase
{
    public function testRegisterPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sign-up');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Sign up');
    }
}
