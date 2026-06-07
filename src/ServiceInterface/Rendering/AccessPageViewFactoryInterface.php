<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\ServiceInterface\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\Form\FormView;

interface AccessPageViewFactoryInterface
{
    /**
     * @param array<int, mixed> $events
     */
    public function home(AccessEntity $user, array $events): AccessPageView;

    /**
     * @param array<int, mixed> $events
     */
    public function overview(AccessEntity $user, array $events): AccessPageView;

    public function verifyEmail(AccessEntity $user, FormView $form): AccessPageView;

    public function requestPhoneVerification(AccessEntity $user, FormView $form): AccessPageView;

    public function confirmPhoneVerification(AccessEntity $user, FormView $form): AccessPageView;

    public function secondFactor(
        AccessEntity $user,
        FormView $form,
        ?AccessSecondFactorEnrollmentDto $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): AccessPageView;

    public function sessions(AccessEntity $user): AccessPageView;

    /**
     * @param array<int, mixed> $events
     */
    public function securityEvents(array $events): AccessPageView;

    public function password(AccessEntity $user, FormView $form): AccessPageView;

    /**
     * @param array<int, mixed> $users
     */
    public function operatorUsers(array $users): AccessPageView;

    /**
     * @param array<int, mixed> $events
     */
    public function operatorUserDetail(AccessEntity $user, array $events): AccessPageView;

    /**
     * @param array<int, mixed> $events
     */
    public function operatorSecurityEvents(array $events): AccessPageView;

    public function register(FormView $form): AccessPageView;

    public function signIn(FormView $form): AccessPageView;

    public function secondFactorChallenge(AccessEntity $user, FormView $form): AccessPageView;

    public function requestRecovery(FormView $form): AccessPageView;

    public function resetRecovery(FormView $form): AccessPageView;

    public function resetPasswordRequest(FormView $form): AccessPageView;

    public function resetPasswordCheckEmail(): AccessPageView;

    public function resetPassword(FormView $form): AccessPageView;
}
