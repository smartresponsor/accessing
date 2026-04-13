<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingAuthenticationRoutesTest extends WebTestCase
{
    public function testHomePageRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/sign-in');
    }

    public function testHomePagePostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/');

        self::assertResponseStatusCodeSame(405);
    }

    public function testSignInPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sign-in');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Sign in');
    }

    public function testSignOutGetIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sign-out');

        self::assertResponseStatusCodeSame(405);
    }

    public function testOverviewRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/overview');

        self::assertResponseRedirects('/sign-in');
    }

    public function testOverviewPostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/overview');

        self::assertResponseStatusCodeSame(405);
    }
}
