<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines the credential request service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessCredentialRequestService
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
    public function __invoke(): Response|InterfaceTemplateRenderableInterface
    {
        $form = $this->formFactory->createBuilder()->add('email', EmailType::class)->getForm();
        $factoryMethod = 'resetPasswordRequest';

        return $this->pageResponder->respond($this->pageViewFactory->{$factoryMethod}($form->createView()));
    }
}
