<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ResponderInterface\Rendering;

use App\Accessing\DTO\AccessPageViewDTO;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the page responder interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPageResponderInterface
{
    /**
     * Executes the respond operation within the canonical Accessing component workflow.
     */
    public function respond(AccessPageViewDTO $pageView): Response;
}
