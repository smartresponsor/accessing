<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the reset password request type type and its canonical responsibility within the Accessing component.
 */
final class AccessResetPasswordRequestType extends AbstractType
{
    /**
     * Executes the build form operation within the canonical Accessing component workflow.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email address',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Send reset link',
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
        ]);
    }
}
