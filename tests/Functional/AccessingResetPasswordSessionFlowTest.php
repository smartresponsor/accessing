<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingResetPasswordSessionFlowTest extends WebTestCase
{
    public function testResetPasswordPlainRouteRedirectsWhenNoSessionTokenExists(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password/reset');

        self::assertResponseRedirects('/reset-password');
    }

    public function testResetPasswordPlainRoutePostRedirectsWhenNoSessionTokenExists(): void
    {
        $client = static::createClient();
        $client->request('POST', '/reset-password/reset');

        self::assertResponseRedirects('/reset-password');
    }
}
