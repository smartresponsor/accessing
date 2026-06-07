<?php

declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Surface\InterfaceSurfaceRenderableInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessOperatorSurfaceBuilder
{
    public function securityEvents(
        AccessSecurityEventRepositoryInterface $securityEventRepository,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|InterfaceSurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->operatorSecurityEvents(
            $securityEventRepository->findRecentEvents(150),
        ));
    }
}
