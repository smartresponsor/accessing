<?php

declare(strict_types=1);

namespace App\Accessing\ProviderInterface\Context;

use App\Accessing\Context\AccessCurrentContext;

/**
 * Defines the current context provider interface type and its canonical responsibility within the Accessing component.
 */
interface AccessCurrentContextProviderInterface
{
    /**
     * Executes the current operation within the canonical Accessing component workflow.
     */
    public function current(): ?AccessCurrentContext;
}
