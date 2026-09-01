<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class AccessApiRouteDeclarationTest extends TestCase
{
    public function testAccessApiRouteDeclarationContainsTheCanonicalRoutes(): void
    {
        $routes = Yaml::parseFile(__DIR__.'/../../config/platform/routes/api/access.yaml');

        self::assertIsArray($routes);
        $signinRoute = $routes['api.access.signin'] ?? null;
        self::assertIsArray($signinRoute);

        self::assertSame(
            [
                'api.access.signin',
                'api.access.refresh',
                'api.access.register',
                'api.access.logout',
                'api.access.session',
                'api.access.verification.resend',
                'api.access.verification.confirm',
                'api.access.second_factor.challenge',
                'api.access.second_factor.verify',
                'api.access.passkey.registration.options',
                'api.access.passkey.registration.complete',
                'api.access.passkey.authentication.options',
                'api.access.passkey.authentication.complete',
                'api.access.recovery.request',
                'api.access.recovery.reset',
            ],
            array_keys($routes),
        );

        self::assertSame('/api/access/signin', $signinRoute['path'] ?? null);
        self::assertSame('api.access.signin', $signinRoute['routeKey'] ?? null);
        self::assertSame('App\\Accessing\\Service\\Http\\Api\\Access\\AccessApiFlowService', $signinRoute['service'] ?? null);
        self::assertSame('signIn', $signinRoute['action'] ?? null);

        self::assertArrayNotHasKey('template', $signinRoute);
        self::assertArrayNotHasKey('parser', $signinRoute);
    }
}
