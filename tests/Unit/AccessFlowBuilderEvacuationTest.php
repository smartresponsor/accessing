<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AccessFlowBuilderEvacuationTest extends TestCase
{
    /**
     * @return iterable<string, array{0: class-string|string}>
     */
    public static function evacuatedAccessFlowBuilders(): iterable
    {
        yield 'security surface builder' => ['App\\Accessing\\Builder\\AccessSecuritySurfaceBuilder'];
        yield 'reset password surface builder' => ['App\\Accessing\\Builder\\AccessResetPasswordSurfaceBuilder'];
        yield 'generic access surface builder' => ['App\\Accessing\\Builder\\AccessSurfaceBuilder'];
        yield 'operator surface builder' => ['App\\Accessing\\Builder\\AccessOperatorSurfaceBuilder'];
    }

    /**
     * @dataProvider evacuatedAccessFlowBuilders
     *
     * @param class-string|string $builderClass
     */
    public function testAccessFlowBuildersArePhysicallyEvacuated(string $builderClass): void
    {
        self::assertFalse(
            class_exists($builderClass),
            sprintf('Access flow handling must live in Service/Http/Access, not in legacy builder %s.', $builderClass),
        );
    }
}
