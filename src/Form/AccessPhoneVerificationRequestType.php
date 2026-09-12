<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Form;

use App\Accessing\DTO\AccessPhoneVerificationRequestDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Defines the phone verification request type type and its canonical responsibility within the Accessing component.
 */
final class AccessPhoneVerificationRequestType extends AbstractType
{
    /**
     * Executes the build form operation within the canonical Accessing component workflow.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('phoneNumber', TextType::class, [
            'label' => 'Phone number',
        ]);
    }

    /**
     * Executes the configure options operation within the canonical Accessing component workflow.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => AccessPhoneVerificationRequestDTO::class,
        ]);
    }
}
