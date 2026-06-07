<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form\Access\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccessUserBulkType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'access_user_bulk';
    }

    /** @param array<string, mixed> $options */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('operation', ChoiceType::class, [
                'label' => 'Bulk operation',
                'choices' => [
                    'Archive' => 'archive',
                    'Restore' => 'restore',
                    'Delete' => 'delete',
                ],
            ])
            ->add('userReferences', TextareaType::class, [
                'label' => 'User references',
                'help' => 'One user id, slug, or email per line.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}
