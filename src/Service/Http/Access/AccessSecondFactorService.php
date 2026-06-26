<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessSecondFactorService
{
    public function __construct(
        private AccessOwnerResolveService $ownerResolveService,
        private FormFactoryInterface $formFactory,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(): Response|InterfaceTemplateRenderableInterface
    {
        $user = $this->ownerResolveService->requireAccess();
        $form = $this->formFactory->createBuilder()->add('code', TextType::class)->getForm();

        return $this->pageResponder->respond($this->pageViewFactory->secondFactor(
            $user,
            $form->createView(),
            null,
            $user->isSecondFactorEnabled(),
            false,
        ));
    }
}
