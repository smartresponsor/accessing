<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessIndexService
{
    public function __construct(
        private AccessRepositoryInterface $userRepository,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(): Response|InterfaceTemplateRenderableInterface
    {
        return $this->pageResponder->respond($this->pageViewFactory->operatorUsers(
            $this->userRepository->findRecentUsers(100),
        ));
    }
}
