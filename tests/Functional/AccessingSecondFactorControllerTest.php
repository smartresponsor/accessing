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

        self::assertResponseRedirects('/login');
    }

    public function testSecondFactorEnableRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/second-factor/enable');

        self::assertResponseRedirects('/login');
    }
}
