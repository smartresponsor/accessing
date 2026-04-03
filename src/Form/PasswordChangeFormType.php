<?php

declare(strict_types=1);

namespace App\Form;

use App\Dto\PasswordChangeDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PasswordChangeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('currentPassword', PasswordType::class, ['attr' => ['autocomplete' => 'current-password']])
            ->add('newPassword', PasswordType::class, ['attr' => ['autocomplete' => 'new-password']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordChangeDto::class,
        ]);
    }
}
