<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access\User;

use Symfony\Component\HttpFoundation\Response;

final readonly class AccessUserExportService
{
    public function __invoke(): Response
    {
        throw AccessUserCrudSkeletonException::unsupported('access.user.export');
    }
}
