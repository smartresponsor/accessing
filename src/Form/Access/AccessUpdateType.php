<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form\Access;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccessUpdateType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'access_update';
    }

    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Display name',
                'required' => false,
            ])
            ->add('phoneNumber', TelType::class, [
                'label' => 'Phone number',
                'required' => false,
                'attr' => ['autocomplete' => 'tel'],
            ])
            ->add('secondFactorEnabled', CheckboxType::class, [
                'label' => 'Second factor enabled',
                'required' => false,
            ])
            ->add('locked', CheckboxType::class, [
                'label' => 'Locked',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
