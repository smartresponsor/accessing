<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RouterInterface;

final class AccessProducerRouteBoundaryTest extends KernelTestCase
{
    public function testAccessingDoesNotRegisterStandaloneProducerRoutes(): void
    {
        self::bootKernel();

        /** @var RouterInterface $router */
        $router = self::getContainer()->get(RouterInterface::class);
        $routes = $router->getRouteCollection();

        $forbiddenRouteNames = [
            'accessing_home',
            'accessing_sign_up',
            'accessing_sign_in_submit',
            'accessing_operator_users',
            'accessing_operator_security_events',
        ];

        foreach ($forbiddenRouteNames as $routeName) {
            self::assertNull($routes->get($routeName), sprintf('Accessing route "%s" must be host-owned.', $routeName));
        }
    }
}
