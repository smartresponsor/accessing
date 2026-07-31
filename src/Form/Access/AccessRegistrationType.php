<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form\Access;

use App\Accessing\Dto\AccessRegistrationRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class AccessRegistrationType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'access_registration';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $controlClass = 'interfacing-form-control ant-input ant-input-lg p-inputtext p-component';
        $labelClass = 'interfacing-form-label ant-form-item-label';
        $rowClass = 'interfacing-form-row ant-form-item p-field';

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => [
                    'class' => $controlClass,
                    'data-interfacing-provider-control' => 'input',
                    'data-interfacing-provider-control-provider' => 'antd-pro',
                    'data-interfacing-provider-control-secondary-provider' => 'primereact',
                    'autocomplete' => 'email',
                ],
                'label_attr' => ['class' => $labelClass],
                'row_attr' => ['class' => $rowClass],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'The passwords must match.',
                'first_options' => [
                    'label' => 'Password',
                    'constraints' => [
                        new Assert\NotBlank(message: 'Enter a password.'),
                        new Assert\Length(min: 10, max: 255, minMessage: 'Use at least {{ limit }} characters.'),
                        new Assert\Regex(pattern: '/[a-z]/', message: 'Add a lowercase letter.'),
                        new Assert\Regex(pattern: '/[A-Z]/', message: 'Add an uppercase letter.'),
                        new Assert\Regex(pattern: '/\d/', message: 'Add a number.'),
                        new Assert\Regex(pattern: '/[^A-Za-z0-9]/', message: 'Add a symbol.'),
                    ],
                    'attr' => [
                        'class' => $controlClass,
                        'data-interfacing-provider-control' => 'password',
                        'data-interfacing-provider-control-provider' => 'antd-pro',
                        'data-interfacing-provider-control-secondary-provider' => 'primereact',
                        'data-access-password-primary' => 'true',
                        'autocomplete' => 'new-password',
                    ],
                    'label_attr' => ['class' => $labelClass],
                    'row_attr' => ['class' => $rowClass],
                ],
                'second_options' => [
                    'label' => 'Confirm password',
                    'attr' => [
                        'class' => $controlClass,
                        'data-interfacing-provider-control' => 'password-confirmation',
                        'data-interfacing-provider-control-provider' => 'antd-pro',
                        'data-interfacing-provider-control-secondary-provider' => 'primereact',
                        'data-access-password-confirmation' => 'true',
                        'autocomplete' => 'new-password',
                    ],
                    'label_attr' => ['class' => $labelClass],
                    'row_attr' => ['class' => $rowClass],
                ],
            ])
            ->add('phoneNumber', TelType::class, [
                'required' => false,
                'label' => 'Phone number',
                'help' => 'Optional. Use an international number, for example +1 555 123 4567.',
                'help_attr' => ['class' => 'interfacing-field-help'],
                'constraints' => [
                    new Assert\Length(max: 32),
                    new Assert\Regex(
                        pattern: '/^$|^\+?[1-9](?:[\s().-]*\d){7,14}$/',
                        message: 'Enter a valid international phone number, for example +1 555 123 4567.',
                    ),
                ],
                'attr' => [
                    'class' => $controlClass,
                    'data-interfacing-provider-control' => 'telephone-input',
                    'data-interfacing-provider-control-provider' => 'antd-pro',
                    'data-interfacing-provider-control-secondary-provider' => 'primereact',
                    'data-access-phone' => 'true',
                    'autocomplete' => 'tel',
                    'inputmode' => 'tel',
                    'placeholder' => '+1 555 123 4567',
                ],
                'label_attr' => ['class' => $labelClass],
                'row_attr' => ['class' => $rowClass],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'csrf_protection' => true,
            'data_class' => AccessRegistrationRequest::class,
            'attr' => [
                'class' => 'interfacing-form ant-form ant-form-vertical p-fluid',
                'data-interfacing-provider-form' => 'access-register',
                'data-interfacing-provider' => 'ant-design-pro',
                'data-interfacing-secondary-provider' => 'primereact',
                'data-interfacing-access-form' => 'signup',
            ],
        ]);
    }
}
