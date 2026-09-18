<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Responder\Rendering;

use App\Accessing\DTO\AccessPageViewDTO;
use App\Accessing\Resolver\Rendering\AccessPageTemplateResolver;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Interfacing\ServiceInterface\Rendering\InterfaceRendererInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the twig page responder type and its canonical responsibility within the Accessing component.
 */
final class AccessTwigPageResponder implements AccessPageResponderInterface
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private readonly AccessPageTemplateResolver $templateResolver,
        private readonly InterfaceRendererInterface $renderer,
    ) {
    }

    /**
     * Executes the respond operation within the canonical Accessing component workflow.
     */
    public function respond(AccessPageViewDTO $pageView): Response
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
