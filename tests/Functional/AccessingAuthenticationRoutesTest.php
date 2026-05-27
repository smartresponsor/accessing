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

    public function testSignInTrailingSlashRedirectsToCanonicalRoute(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sign-in/');

        self::assertResponseRedirects('/sign-in');
    }

    public function testCanonicalAuthenticationRoutesAreRegistered(): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $routeCollection = $router->getRouteCollection();

        self::assertSame('/sign-in', $routeCollection->get('accessing_sign_in')?->getPath());
        self::assertSame('/sign-in/', $routeCollection->get('accessing_sign_in_trailing_slash')?->getPath());
        self::assertSame('/sign-up', $routeCollection->get('accessing_sign_up')?->getPath());
        self::assertSame('/login', $routeCollection->get('accessing_login')?->getPath());
        self::assertSame('/forgot-password', $routeCollection->get('accessing_forgot_password')?->getPath());
        self::assertSame('/recover', $routeCollection->get('accessing_recover')?->getPath());
        self::assertSame('/sign-out', $routeCollection->get('accessing_sign_out')?->getPath());
        self::assertSame('/switch-account', $routeCollection->get('accessing_switch_account')?->getPath());
        self::assertSame('/', $routeCollection->get('accessing_home')?->getPath());
    }

    public function testSignOutGetRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sign-out');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSignOutPostRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('POST', '/sign-out');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSwitchAccountPostRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('POST', '/switch-account');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSwitchAccountGetRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/switch-account');

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

        self::assertNotContains('/logout', $registeredPaths);
        self::assertNotContains('/dashboard', $registeredPaths);
    }

    public function testOverviewRouteIsNotRegistered(): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        self::assertNull($router->getRouteCollection()->get('accessing_overview'));
    }
}
