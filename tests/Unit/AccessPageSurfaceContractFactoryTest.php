<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Factory\Surface\AccessPageSurfaceContractFactory;
use PHPUnit\Framework\TestCase;

final class AccessPageSurfaceContractFactoryTest extends TestCase
{
    /**
     * @dataProvider mappedViews
     */
    public function testItMapsPageViewsToCanonicalInterfacingTemplates(string $view, string $expectedTemplate): void
    {
        $factory = new AccessPageSurfaceContractFactory();

        self::assertSame($expectedTemplate, $factory->create(new AccessPageView($view))->templateName());
    }

    public function testItRejectsUnmappedViews(): void
    {
        $factory = new AccessPageSurfaceContractFactory();

        $this->expectException(\LogicException::class);
        $factory->create(new AccessPageView('access.unknown'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function mappedViews(): array
    {
        return [
            'access overview' => ['access.overview', 'access/overview.html.twig'],
            'verify email' => ['access.verify_email', 'access/verify_email.html.twig'],
            'reset request' => ['access.reset_password_request', 'access/reset_password/request.html.twig'],
            'sign in' => ['access.sign_in', 'access/sign-in/index.html.twig'],
        ];
    }
}
