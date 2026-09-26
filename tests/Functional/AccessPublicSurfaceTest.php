<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessPublicSurfaceTest extends WebTestCase
{
    public function testSignInSurfaceIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/access/signin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Sign in');
    }

    public function testRegistrationSurfaceIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/access/register');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Register access');
    }

    public function testRecoverySurfaceIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/access/recover');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Recover access');
    }

    public function testPasswordResetRequestSurfaceIsReachable(): void
    {
        $client = static::createClient();
        $client->request('GET', '/access/reset/password/request');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Request password reset');
    }
}
