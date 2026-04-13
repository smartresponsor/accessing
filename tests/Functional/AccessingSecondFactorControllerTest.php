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

    public function testSecondFactorPagePostRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/second-factor');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSecondFactorDisableRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/second-factor/disable');

        self::assertResponseRedirects('/sign-in');
    }

    public function testSecondFactorDisableGetIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/second-factor/disable');

        self::assertResponseStatusCodeSame(405);
    }
}
