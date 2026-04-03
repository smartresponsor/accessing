<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\AccountRegistrationRequestDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccountRegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class)
            ->add('emailAddress', EmailType::class)
            ->add('plainPassword', PasswordType::class, ['attr' => ['autocomplete' => 'new-password']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountRegistrationRequestDto::class,
        ]);
    }
}
