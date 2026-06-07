<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use Symfony\Component\HttpFoundation\Response;

final readonly class AccessNewService
{
    public function __invoke(): Response
    {
        throw AccessCrudSkeletonException::unsupported('access.new');
    }
}
