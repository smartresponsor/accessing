<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessSecurityEventsService
{
    public function __construct(
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(): Response|InterfaceTemplateRenderableInterface
    {
        return $this->pageResponder->respond($this->pageViewFactory->securityEvents(
            $this->securityEventRepository->findRecentEvents(),
        ));
    }
}
