<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessUserEntity;
use Symfony\Component\Form\FormView;

interface AccessPageViewFactoryInterface
{
    /**
     * @param array<int, mixed> $events
     */
    public function home(AccessUserEntity $user, array $events): AccessPageView;

    /**
     * @param array<int, mixed> $events
     */
    public function overview(AccessUserEntity $user, array $events): AccessPageView;

    public function verifyEmail(AccessUserEntity $user, FormView $form): AccessPageView;

    public function requestPhoneVerification(AccessUserEntity $user, FormView $form): AccessPageView;

    public function confirmPhoneVerification(AccessUserEntity $user, FormView $form): AccessPageView;

    public function secondFactor(
        AccessUserEntity $user,
        FormView $form,
        ?AccessSecondFactorEnrollmentDto $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): AccessPageView;

    public function sessions(AccessUserEntity $user): AccessPageView;

    /**
     * @param array<int, mixed> $events
     */
    public function securityEvents(array $events): AccessPageView;

    public function password(AccessUserEntity $user, FormView $form): AccessPageView;

    /**
     * @param array<int, mixed> $users
     */
    public function operatorUsers(array $users): AccessPageView;

    /**
     * @param array<int, mixed> $events
     */
    public function operatorUserDetail(AccessUserEntity $user, array $events): AccessPageView;

    /**
     * @param array<int, mixed> $events
     */
    public function operatorSecurityEvents(array $events): AccessPageView;

    public function register(FormView $form): AccessPageView;

    public function signIn(FormView $form): AccessPageView;

    public function secondFactorChallenge(AccessUserEntity $user, FormView $form): AccessPageView;

    public function requestRecovery(FormView $form): AccessPageView;

    public function resetRecovery(FormView $form): AccessPageView;

    public function resetPasswordRequest(FormView $form): AccessPageView;

    public function resetPasswordCheckEmail(): AccessPageView;

    public function resetPassword(FormView $form): AccessPageView;
}
