<?php

declare(strict_types=1);

namespace App\Accessing\Form\Config;

use App\Accessing\Value\Form\Config\AccessingEnvironmentConfigData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AccessingEnvironmentConfigFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mailerSender', TextType::class, [
                'label' => 'ACCESSING_MAILER_SENDER',
                'required' => true,
            ])
            ->add('phoneVerificationProvider', ChoiceType::class, [
                'label' => 'ACCESSING_PHONE_VERIFICATION_PROVIDER',
                'choices' => [
                    'Fake provider' => 'fake',
                    'Null provider' => 'null',
                ],
                'required' => true,
            ])
            ->add('sessionMaxIdleDays', IntegerType::class, [
                'label' => 'ACCESSING_SESSION_MAX_IDLE_DAYS',
                'required' => true,
                'empty_data' => '30',
            ])
            ->add('recoveryCodeTtlMinutes', IntegerType::class, [
                'label' => 'ACCESSING_RECOVERY_CODE_TTL_MINUTES',
                'required' => true,
                'empty_data' => '30',
            ])
            ->add('verificationCodeTtlMinutes', IntegerType::class, [
                'label' => 'ACCESSING_VERIFICATION_CODE_TTL_MINUTES',
                'required' => true,
                'empty_data' => '10',
            ])
            ->add('accountLockThreshold', IntegerType::class, [
                'label' => 'ACCESSING_ACCOUNT_LOCK_THRESHOLD',
                'required' => true,
                'empty_data' => '5',
            ])
            ->add('accountLockMinutes', IntegerType::class, [
                'label' => 'ACCESSING_ACCOUNT_LOCK_MINUTES',
                'required' => true,
                'empty_data' => '15',
            ])
            ->add('save', SubmitType::class, ['label' => 'Save pending'])
            ->add('apply', SubmitType::class, ['label' => 'Apply now', 'attr' => ['class' => 'btn btn-primary']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccessingEnvironmentConfigData::class,
        ]);
    }
}
