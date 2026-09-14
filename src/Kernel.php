<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Defines the kernel type and its canonical responsibility within the Accessing component.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * Executes the get cache dir operation within the canonical Accessing component workflow.
     */
    public function getCacheDir(): string
    {
        if ('test' === $this->environment) {
            return sprintf('%s/accessing-test-cache-%d', sys_get_temp_dir(), getmypid());
        }

        return parent::getCacheDir();
    }
}
