<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Responder\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Resolver\Rendering\AccessPageTemplateResolver;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Interfacing\ServiceInterface\Rendering\InterfaceRendererInterface;
use Symfony\Component\HttpFoundation\Response;

final class AccessTwigPageResponder implements AccessPageResponderInterface
{
    public function __construct(
        private readonly AccessPageTemplateResolver $templateResolver,
        private readonly InterfaceRendererInterface $renderer,
    ) {
    }

    public function respond(AccessPageView $pageView): Response
    {
        return $this->renderer->render(
            $this->templateResolver->resolve($pageView->view),
            [
                'word' => 'access',
                'view' => $pageView->view,
                ...$pageView->parameters,
            ],
            $pageView->statusCode,
        );
    }
}
