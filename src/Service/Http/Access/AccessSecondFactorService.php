<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the second factor service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSecondFactorService
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessOwnerResolveService $ownerResolveService,
        private FormFactoryInterface $formFactory,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    /**
     * Executes the __invoke operation within the canonical Accessing component workflow.
     */
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
