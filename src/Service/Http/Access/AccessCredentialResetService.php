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

final readonly class AccessCredentialResetService
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(): Response|InterfaceTemplateRenderableInterface
    {
        $form = $this->formFactory->createBuilder()
            ->add('code', TextType::class)
            ->add('credential', TextType::class)
            ->getForm();
        $factoryMethod = 'resetPassword';

        return $this->pageResponder->respond($this->pageViewFactory->{$factoryMethod}($form->createView()));
    }
}
