<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingSessionAndSecurityRoutesTest extends WebTestCase
{
    public function testSessionsPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/accessing/sessions');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSessionsPagePostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/accessing/sessions');

        self::assertResponseStatusCodeSame(405);
    }

    public function testSecurityEventsPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/accessing/security-events');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSecurityEventsPagePostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/accessing/security-events');

        self::assertResponseStatusCodeSame(405);
    }
}
