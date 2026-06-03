<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessAuthenticationEntryPointTest extends WebTestCase
{
    public function testSessionsRedirectGuestToCanonicalSignInAndStoresTargetPath(): void
    {
        $client = static::createClient();
        $client->request('GET', '/accessing/sessions');

        self::assertResponseRedirects('/access/signin');
        self::assertSame(
            'http://localhost/accessing/sessions',
            $client->getRequest()->getSession()->get('_security.main.target_path'),
        );
    }
}
