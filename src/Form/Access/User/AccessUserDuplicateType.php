<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form\Access\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccessUserDuplicateType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'access_user_duplicate';
    }

    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sourceUserReference', TextType::class, [
                'label' => 'Source user reference',
            ])
            ->add('email', EmailType::class, [
                'label' => 'New email address',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('displayName', TextType::class, [
                'label' => 'Display name',
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
