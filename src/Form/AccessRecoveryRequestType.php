<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form;

use App\Accessing\DTO\AccessRecoveryRequestDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the recovery request type type and its canonical responsibility within the Accessing component.
 */
final class AccessRecoveryRequestType extends AbstractType
{
    /**
     * Executes the get block prefix operation within the canonical Accessing component workflow.
     */
    public function getBlockPrefix(): string
    {
        return 'access_recovery_request';
    }

    /**
     * Executes the build form operation within the canonical Accessing component workflow.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('emailAddress', EmailType::class, [
            'label' => 'Email address',
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
            'data_class' => AccessRecoveryRequestDTO::class,
        ]);
    }
}
