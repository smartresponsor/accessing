<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingResetPasswordSessionFlowTest extends WebTestCase
{
    public function testResetPasswordPlainRouteRedirectsWhenNoSessionTokenExists(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forgot-password/reset');

        self::assertResponseRedirects('/forgot-password');
    }

    public function testResetPasswordPlainRoutePostRedirectsWhenNoSessionTokenExists(): void
    {
        $client = static::createClient();
        $client->request('POST', '/forgot-password/reset');

        self::assertResponseRedirects('/forgot-password');
    }
}
