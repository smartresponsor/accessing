<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form\Access;

use App\Accessing\Dto\AccessSignInRequestDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccessSignInType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'access_sign_in';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $controlClass = 'interfacing-form-control ant-input ant-input-lg p-inputtext p-component';
        $labelClass = 'interfacing-form-label ant-form-item-label';
        $rowClass = 'interfacing-form-row ant-form-item p-field';

        $builder
            ->add('emailAddress', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'class' => $controlClass,
                    'data-interfacing-provider-control' => 'email-input',
                    'data-interfacing-provider-control-provider' => 'antd-pro',
                    'data-interfacing-provider-control-secondary-provider' => 'primereact',
                    'autocomplete' => 'email',
                ],
                'label_attr' => ['class' => $labelClass],
                'row_attr' => ['class' => $rowClass],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'class' => $controlClass,
                    'data-interfacing-provider-control' => 'password',
                    'data-interfacing-provider-control-provider' => 'antd-pro',
                    'data-interfacing-provider-control-secondary-provider' => 'primereact',
                    'data-access-password-primary' => 'true',
                    'autocomplete' => 'current-password',
                ],
                'label_attr' => ['class' => $labelClass],
                'row_attr' => ['class' => $rowClass],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'csrf_protection' => false,
            'data_class' => AccessSignInRequestDto::class,
            'attr' => [
                'class' => 'interfacing-form ant-form ant-form-vertical p-fluid',
                'data-interfacing-provider-form' => 'access-signin',
                'data-interfacing-provider' => 'ant-design-pro',
                'data-interfacing-secondary-provider' => 'primereact',
                'data-interfacing-access-form' => 'signin',
            ],
        ]);
    }
}
