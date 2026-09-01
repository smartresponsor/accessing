<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ResponderInterface\Rendering;

use App\Accessing\Dto\AccessPageView;
use Symfony\Component\HttpFoundation\Response;

interface AccessPageResponderInterface
{
    public function respond(AccessPageView $pageView): Response;
}
