<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;

interface AccessPageResponderInterface
{
    public function respond(AccessPageView $pageView): InterfaceTemplateRenderableInterface;
}
