<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingEmailVerificationControllerTest extends WebTestCase
{
    public function testEmailVerificationRequestGetIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('GET', '/verification/email/request');

        self::assertResponseStatusCodeSame(405);
    }

    public function testEmailVerificationRequestPostRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('POST', '/verification/email/request');

        self::assertResponseRedirects('/login');
    }

    public function testEmailVerificationConfirmPostIsNotAllowed(): void
    {
        $client = static::createClient();
        $client->request('POST', '/verification/email/invalid-token');

        self::assertResponseStatusCodeSame(405);
    }

    public function testInvalidEmailVerificationTokenReturnsNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/verification/email/invalid-token');

        self::assertResponseStatusCodeSame(404);
    }
}
