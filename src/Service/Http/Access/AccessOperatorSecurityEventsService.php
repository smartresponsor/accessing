<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the operator security events service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessOperatorSecurityEventsService
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    /**
     * Executes the __invoke operation within the canonical Accessing component workflow.
     */
    public function __invoke(): Response|InterfaceTemplateRenderableInterface
    {
        return $this->pageResponder->respond($this->pageViewFactory->operatorSecurityEvents(
            $this->securityEventRepository->findRecentEvents(150),
        ));
    }
}
