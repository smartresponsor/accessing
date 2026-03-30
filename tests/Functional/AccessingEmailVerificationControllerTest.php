<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingEmailVerificationControllerTest extends WebTestCase
{
    public function testEmailVerificationRequestRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/verification/email/request');

        self::assertResponseRedirects('/login');
    }

    public function testInvalidEmailVerificationTokenReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/verification/email/invalid-token');

        self::assertResponseStatusCodeSame(404);
    }
}
