<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Rendering;

use App\Accessing\Dto\PageView;
use App\Interfacing\ServiceInterface\Presentation\SurfaceRenderableInterface;

interface PageResponderInterface
{
    public function respond(PageView $pageView): SurfaceRenderableInterface;
}
