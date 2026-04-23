<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Rendering;

use App\Accessing\Dto\PageView;
use Symfony\Component\HttpFoundation\Response;

interface PageResponderInterface
{
    public function respond(PageView $pageView): Response;
}
