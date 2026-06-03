<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Responder\Rendering;

use App\Accessing\Dto\PageView;
use App\Accessing\Factory\Surface\AccessingPageSurfaceContractFactory;
use App\Accessing\ServiceInterface\Rendering\PageResponderInterface;
use App\Interfacing\ServiceInterface\Presentation\SurfaceRenderableInterface;

final class TwigPageResponder implements PageResponderInterface
{
    public function __construct(
        private readonly AccessingPageSurfaceContractFactory $surfaceContractFactory,
    ) {
    }

    public function respond(PageView $pageView): SurfaceRenderableInterface
    {
        return $this->surfaceContractFactory->create($pageView);
    }
}
