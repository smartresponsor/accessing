<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingAccountSessionControllerTest extends WebTestCase
{
    public function testSessionsRequireAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sessions');

        self::assertResponseRedirects('/sign-in');
    }
}
