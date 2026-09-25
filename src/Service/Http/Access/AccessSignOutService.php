<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines the sign out service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSignOutService
{
    /**
     * Executes the __invoke operation within the canonical Accessing component workflow.
     */
    public function __invoke(): RedirectResponse
    {
        return new RedirectResponse('/access/signin');
    }
}
