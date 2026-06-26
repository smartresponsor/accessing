<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class AccessOperatorUserDetailService
{
    public function __construct(
        private AccessRepositoryInterface $userRepository,
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(int $id): Response|InterfaceTemplateRenderableInterface
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
}
