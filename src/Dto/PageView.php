<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Dto;

/**
 * Logical page payload emitted by the component.
 *
 * The view key is component-owned and intentionally does not expose Twig
 * template paths so Bridging can map it to Interfacing later.
 *
 * @phpstan-type PageParameters array<string, mixed>
 */
final readonly class PageView
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        public string $view,
        public array $parameters = [],
        public int $statusCode = 200,
    ) {
    }
}
