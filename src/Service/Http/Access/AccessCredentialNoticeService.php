<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessCredentialNoticeService
{
    public function __construct(
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(): Response|InterfaceTemplateRenderableInterface
    {
        $factoryMethod = 'resetPasswordCheckEmail';

        return $this->pageResponder->respond($this->pageViewFactory->{$factoryMethod}());
    }
}
