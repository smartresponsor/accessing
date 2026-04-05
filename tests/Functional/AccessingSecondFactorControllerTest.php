<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingSecondFactorControllerTest extends WebTestCase
{
    public function testSecondFactorPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/second-factor');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSecondFactorPagePostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/second-factor');

        self::assertResponseStatusCodeSame(405);
    }

    public function testSecondFactorEnableRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/second-factor/enable');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSecondFactorDisableRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/second-factor/disable');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSecondFactorEnableGetIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/second-factor/enable');

        self::assertResponseStatusCodeSame(405);
    }

    public function testSecondFactorDisableGetIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/second-factor/disable');

        self::assertResponseStatusCodeSame(405);
    }
}
