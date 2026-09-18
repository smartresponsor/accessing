<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Defines the show service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessShowService
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessRepositoryInterface $userRepository,
        private AccessSecurityEventRepositoryInterface $securityEventRepository,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    /**
     * Executes the __invoke operation within the canonical Accessing component workflow.
     */
    public function __invoke(int $id): Response|InterfaceTemplateRenderableInterface
    {
        return $this->showById($id);
    }

    /**
     * Executes the show by id operation within the canonical Accessing component workflow.
     */
    public function showById(int $id): Response|InterfaceTemplateRenderableInterface
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
