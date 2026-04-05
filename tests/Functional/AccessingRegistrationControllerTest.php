<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingRegistrationControllerTest extends WebTestCase
{
    public function testRegisterPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Create account');
    }
}
