<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Responder\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Factory\Surface\AccessPageSurfaceContractFactory;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Interfacing\Contract\Surface\InterfaceSurfaceRenderableInterface;

final class AccessTwigPageResponder implements AccessPageResponderInterface
{
    public function __construct(
        private readonly AccessPageSurfaceContractFactory $surfaceContractFactory,
    ) {
    }

    public function respond(AccessPageView $pageView): InterfaceSurfaceRenderableInterface
    {
        return $this->surfaceContractFactory->create($pageView);
    }
}
