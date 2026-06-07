<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Tests\Unit;

use App\Accessing\Service\Http\Access\AccessResetPasswordFlowService;
use App\Accessing\Service\Http\Access\AccessSecurityFlowService;
use App\Accessing\Service\Http\Access\AccessSurfaceFlowService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AccessFlowServiceSelfContainedContractTest extends TestCase
{
    /**
     * @return iterable<string, array{0: class-string}>
     */
    public static function flowServices(): iterable
    {
        yield 'security flow' => [AccessSecurityFlowService::class];
        yield 'reset password flow' => [AccessResetPasswordFlowService::class];
        yield 'surface flow' => [AccessSurfaceFlowService::class];
    }

    /**
     * @dataProvider flowServices
     *
     * @param class-string $serviceClass
     */
    public function testPublicFlowMethodsDoNotExposeContainerCollaborators(string $serviceClass): void
    {
        $reflectionClass = new \ReflectionClass($serviceClass);

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isConstructor()) {
                continue;
            }

            foreach ($method->getParameters() as $parameter) {
                $type = $parameter->getType();

                if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                    continue;
                }

                self::assertSame(
                    Request::class,
                    $type->getName(),
                    sprintf('%s::%s() exposes collaborator parameter $%s.', $serviceClass, $method->getName(), $parameter->getName()),
                );
            }
        }
    }
}
