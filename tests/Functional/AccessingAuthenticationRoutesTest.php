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
        $client->request('GET', '/accessing/');

        self::assertResponseRedirects('/access/signin');
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
        $client->request('GET', '/access/signin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Sign in');
    }

    public function testCanonicalAuthenticationRoutesAreRegistered(): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $routeCollection = $router->getRouteCollection();

        self::assertSame('/access/signin', $routeCollection->get('accessing_sign_in_submit')?->getPath());
        self::assertSame('/accessing/sign-up', $routeCollection->get('accessing_sign_up')?->getPath());
        self::assertSame('/access/signin', $routeCollection->get('interfacing_welcome_sign_in')?->getPath());
        self::assertSame('/sign-up', $routeCollection->get('interfacing_welcome_sign_up')?->getPath());
        self::assertSame('/accessing/login', $routeCollection->get('accessing_login')?->getPath());
        self::assertSame('/accessing/forgot-password', $routeCollection->get('accessing_forgot_password')?->getPath());
        self::assertSame('/accessing/recover', $routeCollection->get('accessing_recover')?->getPath());
        self::assertSame('/accessing/sign-out', $routeCollection->get('accessing_sign_out')?->getPath());
        self::assertSame('/accessing/switch-account', $routeCollection->get('accessing_switch_account')?->getPath());
        self::assertSame('/accessing/', $routeCollection->get('accessing_home')?->getPath());
    }

    public function testSignOutGetRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/accessing/sign-out');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSignOutPostRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('POST', '/accessing/sign-out');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSwitchAccountPostRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('POST', '/accessing/switch-account');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSwitchAccountGetRedirectsGuestToSignIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/accessing/switch-account');

        self::assertResponseRedirects('/access/signin');
    }

    public function testSecondFactorChallengeWithoutPendingFlowRedirectsToSignIn(): void
    {
        $client = static::createClient();
        $client->request('GET', '/access/signin/second-factor');

        self::assertResponseRedirects('/access/signin');
    }

    public function testRemovedAuthenticationRoutePathsAreNotRegistered(): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        $registeredPaths = array_map(
            static fn ($route): string => $route->getPath(),
            $router->getRouteCollection()->all(),
        );

        self::assertNotContains('/dashboard', $registeredPaths);
    }

    public function testOverviewRouteIsNotRegistered(): void
    {
        /** @var RouterInterface $router */
        $router = static::getContainer()->get(RouterInterface::class);
        self::assertNull($router->getRouteCollection()->get('accessing_overview'));
    }
}
