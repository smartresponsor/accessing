<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\FactoryInterface\Rendering;

use App\Accessing\DTO\AccessPageViewDTO;
use App\Accessing\DTO\AccessSecondFactorEnrollmentDTO;
use App\Accessing\Entity\AccessEntity;
use Symfony\Component\Form\FormView;

/**
 * Defines the page view factory interface type and its canonical responsibility within the Accessing component.
 */
interface AccessPageViewFactoryInterface
{
    /**
     * @param array<int, mixed> $events
     */
    public function home(AccessEntity $user, array $events): AccessPageViewDTO;

    /**
     * @param array<int, mixed> $events
     */
    public function overview(AccessEntity $user, array $events): AccessPageViewDTO;

    /**
     * Executes the verify email operation within the canonical Accessing component workflow.
     */
    public function verifyEmail(AccessEntity $user, FormView $form): AccessPageViewDTO;

    /**
     * Executes the request phone verification operation within the canonical Accessing component workflow.
     */
    public function requestPhoneVerification(AccessEntity $user, FormView $form): AccessPageViewDTO;

    /**
     * Executes the confirm phone verification operation within the canonical Accessing component workflow.
     */
    public function confirmPhoneVerification(AccessEntity $user, FormView $form): AccessPageViewDTO;

    /**
     * Executes the second factor operation within the canonical Accessing component workflow.
     */
    public function secondFactor(
        AccessEntity $user,
        FormView $form,
        ?AccessSecondFactorEnrollmentDTO $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): AccessPageViewDTO;

    /**
     * Executes the sessions operation within the canonical Accessing component workflow.
     */
    public function sessions(AccessEntity $user): AccessPageViewDTO;

    /**
     * @param array<int, mixed> $events
     */
    public function securityEvents(array $events): AccessPageViewDTO;

    /**
     * Executes the password operation within the canonical Accessing component workflow.
     */
    public function password(AccessEntity $user, FormView $form): AccessPageViewDTO;

    /**
     * @param array<int, mixed> $users
     */
    public function operatorUsers(array $users): AccessPageViewDTO;

    /**
     * @param array<int, mixed> $events
     */
    public function operatorUserDetail(AccessEntity $user, array $events): AccessPageViewDTO;

    /**
     * @param array<int, mixed> $events
     */
    public function operatorSecurityEvents(array $events): AccessPageViewDTO;

    /**
     * Executes the register operation within the canonical Accessing component workflow.
     */
    public function register(FormView $form, int $statusCode = 200): AccessPageViewDTO;

    /**
     * Executes the sign in operation within the canonical Accessing component workflow.
     */
    public function signIn(FormView $form, int $statusCode = 200): AccessPageViewDTO;

    /**
     * Executes the second factor challenge operation within the canonical Accessing component workflow.
     */
    public function secondFactorChallenge(AccessEntity $user, FormView $form): AccessPageViewDTO;

    /**
     * Executes the request recovery operation within the canonical Accessing component workflow.
     */
    public function requestRecovery(FormView $form): AccessPageViewDTO;

    /**
     * Executes the reset recovery operation within the canonical Accessing component workflow.
     */
    public function resetRecovery(FormView $form): AccessPageViewDTO;

    /**
     * Executes the reset password request operation within the canonical Accessing component workflow.
     */
    public function resetPasswordRequest(FormView $form): AccessPageViewDTO;

    /**
     * Executes the reset password check email operation within the canonical Accessing component workflow.
     */
    public function resetPasswordCheckEmail(): AccessPageViewDTO;

    /**
     * Executes the reset password operation within the canonical Accessing component workflow.
     */
    public function resetPassword(FormView $form): AccessPageViewDTO;
}
