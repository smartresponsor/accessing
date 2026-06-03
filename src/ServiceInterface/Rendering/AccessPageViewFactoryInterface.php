<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessAccountEntity;
use Symfony\Component\Form\FormView;

interface AccessPageViewFactoryInterface
{
    public function home(AccessAccountEntity $account, array $events): AccessPageView;

    public function overview(AccessAccountEntity $account, array $events): AccessPageView;

    public function verifyEmail(AccessAccountEntity $account, FormView $form): AccessPageView;

    public function requestPhoneVerification(AccessAccountEntity $account, FormView $form): AccessPageView;

    public function confirmPhoneVerification(AccessAccountEntity $account, FormView $form): AccessPageView;

    public function secondFactor(
        AccessAccountEntity $account,
        FormView $form,
        ?AccessSecondFactorEnrollmentDto $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): AccessPageView;

    public function sessions(AccessAccountEntity $account): AccessPageView;

    public function securityEvents(array $events): AccessPageView;

    public function password(AccessAccountEntity $account, FormView $form): AccessPageView;

    public function operatorAccounts(array $accounts): AccessPageView;

    public function operatorAccountDetail(AccessAccountEntity $account, array $events): AccessPageView;

    public function operatorSecurityEvents(array $events): AccessPageView;

    public function register(FormView $form): AccessPageView;

    public function signIn(FormView $form): AccessPageView;

    public function secondFactorChallenge(AccessAccountEntity $account, FormView $form): AccessPageView;

    public function requestRecovery(FormView $form): AccessPageView;

    public function resetRecovery(FormView $form): AccessPageView;

    public function resetPasswordRequest(FormView $form): AccessPageView;

    public function resetPasswordCheckEmail(): AccessPageView;

    public function resetPassword(FormView $form): AccessPageView;
}
