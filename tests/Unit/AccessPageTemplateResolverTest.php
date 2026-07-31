<?php

declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Resolver\Rendering\AccessPageTemplateResolver;
use PHPUnit\Framework\TestCase;

final class AccessPageTemplateResolverTest extends TestCase
{
    /**
     * @dataProvider mappedViews
     */
    public function testItMapsPageViewsToCanonicalInterfacingTemplates(string $view, string $expectedTemplate): void
    {
        $resolver = new AccessPageTemplateResolver();

        self::assertSame($expectedTemplate, $resolver->resolve($view));
    }

    public function testItRejectsUnmappedViews(): void
    {
        $resolver = new AccessPageTemplateResolver();

        $this->expectException(\LogicException::class);
        $resolver->resolve('access.unknown');
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function mappedViews(): array
    {
        return [
            'access overview' => ['access.overview', 'access/overview.html.twig'],
            'verify email' => ['access.verify_email', 'access/verify_email.html.twig'],
            'reset request' => ['access.reset_password_request', 'access/reset-password/request.html.twig'],
            'sign in' => ['access.signin', 'access/signin/index.html.twig'],
        ];
    }
}
