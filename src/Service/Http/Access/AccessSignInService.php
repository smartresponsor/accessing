<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Form\Access\AccessSignInType;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class AccessSignInService
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
    ) {
    }

    public function __invoke(): Response
    {
        $form = $this->formFactory->create(AccessSignInType::class);

        return $this->pageResponder->respond($this->pageViewFactory->signIn($form->createView()));
    }
}
