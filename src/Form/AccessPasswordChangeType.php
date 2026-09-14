<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form;

use App\Accessing\DTO\AccessPasswordChangeDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the password change type type and its canonical responsibility within the Accessing component.
 */
final class AccessPasswordChangeType extends AbstractType
{
    /**
     * Executes the build form operation within the canonical Accessing component workflow.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Current password',
                'attr' => ['autocomplete' => 'current-password'],
            ])
            ->add('newPassword', PasswordType::class, [
                'label' => 'New password',
                'attr' => ['autocomplete' => 'new-password'],
            ]);
    }

    /**
     * Executes the configure options operation within the canonical Accessing component workflow.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => AccessPasswordChangeDTO::class,
        ]);
    }
}
