<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Surface\InterfaceSurfaceRenderableInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessOperatorSecurityEventsService
{
    public function __construct(
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    /**
     * Render operator-level security event index for the Access grammar tree.
     */
    public function securityEvents(): Response|InterfaceSurfaceRenderableInterface
    {
        return $this->pageResponder->respond($this->pageViewFactory->operatorSecurityEvents(
            $this->securityEventRepository->findRecentEvents(150),
        ));
    }
}
