<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingSecurityViewsControllerTest extends WebTestCase
{
    public function testSessionsPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sessions');

        self::assertResponseRedirects('/login');
    }

    public function testSessionsPagePostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/sessions');

        self::assertResponseStatusCodeSame(405);
    }

    public function testSecurityEventsPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/security-events');

        self::assertResponseRedirects('/login');
    }

    public function testSecurityEventsPagePostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/security-events');

        self::assertResponseStatusCodeSame(405);
    }
}
