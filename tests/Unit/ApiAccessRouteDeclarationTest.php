<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class ApiAccessRouteDeclarationTest extends TestCase
{
    public function testApiAccessRouteDeclarationContainsTheFourCanonicalRoutes(): void
    {
        $routes = Yaml::parseFile(__DIR__.'/../../config/platform/routes/api/access.yaml');

        self::assertIsArray($routes);
        self::assertSame(
            [
                'api.access.signin',
                'api.access.register',
                'api.access.logout',
                'api.access.session',
            ],
            array_keys($routes),
        );

        self::assertSame('/api/access/signin', $routes['api.access.signin']['path'] ?? null);
        self::assertSame('api.access.signin', $routes['api.access.signin']['routeKey'] ?? null);
        self::assertSame('App\\Accessing\\Service\\Http\\Api\\Access\\ApiAccessFlowService', $routes['api.access.signin']['service'] ?? null);
        self::assertSame('signIn', $routes['api.access.signin']['action'] ?? null);

        self::assertArrayNotHasKey('template', $routes['api.access.signin']);
        self::assertArrayNotHasKey('parser', $routes['api.access.signin']);
    }
}
