<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingAccountControllerTest extends WebTestCase
{
    public function testHomePageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Account access lifecycle starts here');
    }

    public function testHomePagePostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/');

        self::assertResponseStatusCodeSame(405);
    }

    public function testLoginPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Sign in');
    }

    public function testDashboardRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/dashboard');

        self::assertResponseRedirects('/login');
    }

    public function testDashboardPostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/dashboard');

        self::assertResponseStatusCodeSame(405);
    }
}
