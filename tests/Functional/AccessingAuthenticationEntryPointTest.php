<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccessingAuthenticationEntryPointTest extends WebTestCase
{
    public function testOverviewRedirectsGuestToCanonicalSignInAndStoresTargetPath(): void
    {
        $client = static::createClient();
        $client->request('GET', '/overview');

        self::assertResponseRedirects('/sign-in');
        self::assertSame(
            'http://localhost/overview',
            $client->getRequest()->getSession()->get('_security.main.target_path'),
        );
    }

    public function testSessionsRedirectGuestToCanonicalSignInAndStoresTargetPath(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sessions');

        self::assertResponseRedirects('/sign-in');
        self::assertSame(
            'http://localhost/sessions',
            $client->getRequest()->getSession()->get('_security.main.target_path'),
        );
    }
}
