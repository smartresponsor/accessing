<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\Form\AccessSignInType;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the sign in service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSignInService
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private FormFactoryInterface $formFactory,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    /**
     * Executes the __invoke operation within the canonical Accessing component workflow.
     */
    public function __invoke(): Response
    {
        $form = $this->formFactory->create(AccessSignInType::class);

        return $this->pageResponder->respond($this->pageViewFactory->signIn($form->createView()));
    }
}
