<?php

declare(strict_types=1);

namespace App\Accessing\Form\Config;

use App\Accessing\Value\Config\AccessEnvironmentConfigData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccessEnvironmentConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('mailerSender', TextType::class)
            ->add('phoneVerificationProvider', ChoiceType::class, [
                'choices' => [
                    'Fake' => 'fake',
                    'Null' => 'null',
                ],
            ])
            ->add('sessionMaxIdleDays', IntegerType::class)
            ->add('recoveryCodeTtlMinutes', IntegerType::class)
            ->add('verificationCodeTtlMinutes', IntegerType::class)
            ->add('accountLockThreshold', IntegerType::class)
            ->add('accountLockMinutes', IntegerType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'data_class' => AccessEnvironmentConfigData::class,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'access_environment_config';
    }
}
