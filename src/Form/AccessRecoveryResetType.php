<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form;

use App\Accessing\DTO\AccessRecoveryResetDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the recovery reset type type and its canonical responsibility within the Accessing component.
 */
final class AccessRecoveryResetType extends AbstractType
{
    /**
     * Executes the get block prefix operation within the canonical Accessing component workflow.
     */
    public function getBlockPrefix(): string
    {
        return 'access_recovery_reset';
    }

    /**
     * Executes the build form operation within the canonical Accessing component workflow.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('emailAddress', EmailType::class, [
                'label' => 'Email address',
            ])
            ->add('code', TextType::class, [
                'label' => 'Recovery code',
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
            'csrf_protection' => false,
            'data_class' => AccessRecoveryResetDTO::class,
        ]);
    }
}
