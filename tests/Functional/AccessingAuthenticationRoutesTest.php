<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

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

    public function testCanonicalAuthenticationRoutesAreRegistered(): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $routeCollection = $router->getRouteCollection();

        self::assertSame('/sign-in', $routeCollection->get('accessing_sign_in')?->getPath());
        self::assertSame('/sign-out', $routeCollection->get('accessing_sign_out')?->getPath());
        self::assertSame('/overview', $routeCollection->get('accessing_overview')?->getPath());
    }

    public function testSignOutGetIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sign-out');

        self::assertResponseStatusCodeSame(405);
    }

    public function testSignOutPostRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('POST', '/sign-out');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSecondFactorChallengeWithoutPendingFlowRedirectsToSignIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sign-in/second-factor');

        self::assertResponseRedirects('/sign-in');
    }

    public function testRemovedAuthenticationRoutePathsAreNotRegistered(): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $registeredPaths = array_map(
            static fn ($route): string => $route->getPath(),
            $router->getRouteCollection()->all(),
        );

        self::assertNotContains('/login', $registeredPaths);
        self::assertNotContains('/logout', $registeredPaths);
        self::assertNotContains('/dashboard', $registeredPaths);
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
