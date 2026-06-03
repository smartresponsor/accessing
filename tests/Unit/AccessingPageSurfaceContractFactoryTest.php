<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Dto\PageView;
use App\Accessing\Factory\Surface\AccessingPageSurfaceContractFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AccessingPageSurfaceContractFactoryTest extends TestCase
{
    #[DataProvider('mappedViews')]
    public function testItMapsPageViewsToCanonicalInterfacingTemplates(string $view, string $expectedTemplate): void
    {
        $factory = new AccessingPageSurfaceContractFactory();

        self::assertSame($expectedTemplate, $factory->create(new PageView($view))->templateName());
    }

    public function testItRejectsUnmappedViews(): void
    {
        $factory = new AccessingPageSurfaceContractFactory();

        $this->expectException(\LogicException::class);
        $factory->create(new PageView('account.unknown'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function mappedViews(): array
    {
        return [
            'account overview' => ['account.overview', 'access/account/overview.html.twig'],
            'verify email' => ['account.verify_email', 'access/account/verify_email.html.twig'],
            'reset request' => ['reset_password.request', 'access/reset_password/request.html.twig'],
            'sign in' => ['account.sign_in', 'accessin/signin/index.html.twig'],
        ];
    }
}
