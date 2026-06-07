<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access\User;

use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessUserRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Surface\InterfaceSurfaceRenderableInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class AccessUserShowService
{
    public function __construct(
        private AccessUserRepositoryInterface $userRepository,
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(int $id): Response|InterfaceSurfaceRenderableInterface
    {
        return $this->showById($id);
    }

    public function showById(int $id): Response|InterfaceSurfaceRenderableInterface
    {
        $user = $this->userRepository->findById($id);

        if (null === $user) {
            throw new NotFoundHttpException();
        }

        return $this->pageResponder->respond($this->pageViewFactory->operatorUserDetail(
            $user,
            $this->securityEventRepository->findRecentEventsForUser($user),
        ));
    }

    public function showBySlug(string $slug): Response|InterfaceSurfaceRenderableInterface
    {
        throw AccessUserCrudSkeletonException::unsupported('access.user.show_slug', $slug);
    }
}
