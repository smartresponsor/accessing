<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class AccessSignOutService
{
    public function __invoke(): RedirectResponse
    {
        return new RedirectResponse('/access/signin');
    }
}
