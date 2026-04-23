<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Rendering;

use App\Accessing\Dto\AccessingSecondFactorEnrollmentDto;
use App\Accessing\Dto\PageView;
use App\Accessing\Entity\AccessAccountEntity;
use Symfony\Component\Form\FormView;

interface PageViewFactoryInterface
{
    public function home(AccessAccountEntity $account, array $events): PageView;

    public function overview(AccessAccountEntity $account, array $events): PageView;

    public function verifyEmail(AccessAccountEntity $account, FormView $form): PageView;

    public function requestPhoneVerification(AccessAccountEntity $account, FormView $form): PageView;

    public function confirmPhoneVerification(AccessAccountEntity $account, FormView $form): PageView;

    public function secondFactor(
        AccessAccountEntity $account,
        FormView $form,
        ?AccessingSecondFactorEnrollmentDto $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): PageView;

    public function sessions(AccessAccountEntity $account): PageView;

    public function securityEvents(array $events): PageView;

    public function password(AccessAccountEntity $account, FormView $form): PageView;

    public function operatorAccounts(array $accounts): PageView;

    public function operatorAccountDetail(AccessAccountEntity $account, array $events): PageView;

    public function operatorSecurityEvents(array $events): PageView;

    public function register(FormView $form): PageView;

    public function signIn(FormView $form): PageView;

    public function secondFactorChallenge(AccessAccountEntity $account, FormView $form): PageView;

    public function requestRecovery(FormView $form): PageView;

    public function resetRecovery(FormView $form): PageView;

    public function resetPasswordRequest(FormView $form): PageView;

    public function resetPasswordCheckEmail(): PageView;

    public function resetPassword(FormView $form): PageView;
}
