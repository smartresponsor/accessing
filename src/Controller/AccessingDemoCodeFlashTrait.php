<?php
# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Controller;

trait AccessingDemoCodeFlashTrait
{
    private function addDemoCodeFlash(string $label, string $code): void
    {
        if ($this->getParameter('kernel.environment') === 'prod') {
            return;
        }

        $this->addFlash('secondary', sprintf('%s: %s', $label, $code));
    }
}
