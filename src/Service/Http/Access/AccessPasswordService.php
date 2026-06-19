<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Surface\InterfaceSurfaceRenderableInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessPasswordService
{
    public function __construct(
        private AccessOwnerResolveService $ownerResolveService,
        private FormFactoryInterface $formFactory,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(): Response|InterfaceSurfaceRenderableInterface
    {
        $form = $this->formFactory->createBuilder()
            ->add('currentCredential', TextType::class)
            ->add('newCredential', TextType::class)
            ->getForm();

        return $this->pageResponder->respond($this->pageViewFactory->password(
            $this->ownerResolveService->requireAccess(),
            $form->createView(),
        ));
    }
}
