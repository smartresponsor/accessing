<?php

declare(strict_types=1);

namespace App\Accessing;

use App\Accessing\DependencyInjection\AccessingExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony bundle facade for the Accessing RC component.
 *
 * The component remains responsible for its own business surface.
 * The host application only enables this bundle and imports routes when needed.
 */
final class AccessingBundle extends Bundle
{
    /**
     * Executes the get container extension operation within the canonical Accessing component workflow.
     */
    public function getContainerExtension(): ExtensionInterface
    {
        return parent::getContainerExtension() ?? new AccessingExtension();
    }

    /**
     * Executes the get path operation within the canonical Accessing component workflow.
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
