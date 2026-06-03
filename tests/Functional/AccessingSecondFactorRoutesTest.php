<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingSecondFactorRoutesTest extends WebTestCase
{
    public function testSecondFactorPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/accessing/second-factor');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSecondFactorPagePostRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/accessing/second-factor');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSecondFactorDisableRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/accessing/second-factor/disable');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSecondFactorDisableGetIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/accessing/second-factor/disable');

        self::assertResponseRedirects('/access/signin');
    }
}
