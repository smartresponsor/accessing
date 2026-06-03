<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessResetPasswordSessionFlowTest extends WebTestCase
{
    public function testResetPasswordPlainRouteRedirectsWhenNoSessionTokenExists(): void
    {
        $client = static::createClient();
        $client->request('GET', '/accessing/forgot-password/reset');

        self::assertResponseRedirects('/accessing/forgot-password');
    }

    public function testResetPasswordPlainRoutePostRedirectsWhenNoSessionTokenExists(): void
    {
        $client = static::createClient();
        $client->request('POST', '/accessing/forgot-password/reset');

        self::assertResponseRedirects('/accessing/forgot-password');
    }
}
