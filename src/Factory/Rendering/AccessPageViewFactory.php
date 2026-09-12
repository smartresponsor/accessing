<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Factory\Rendering;

use App\Accessing\DTO\AccessPageViewDTO;
use App\Accessing\DTO\AccessSecondFactorEnrollmentDTO;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use Symfony\Component\Form\FormView;

/**
 * Defines the page view factory type and its canonical responsibility within the Accessing component.
 */
final class AccessPageViewFactory implements AccessPageViewFactoryInterface
{
    /**
     * @param array<int, mixed> $events
     */
    public function home(AccessEntity $user, array $events): AccessPageViewDTO
    {
        return $this->page('access.overview', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function overview(AccessEntity $user, array $events): AccessPageViewDTO
    {
        return $this->page('access.overview', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    /**
     * Executes the verify email operation within the canonical Accessing component workflow.
     */
    public function verifyEmail(AccessEntity $user, FormView $form): AccessPageViewDTO
    {
        return $this->page('access.verify_email', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Executes the request phone verification operation within the canonical Accessing component workflow.
     */
    public function requestPhoneVerification(AccessEntity $user, FormView $form): AccessPageViewDTO
    {
        return $this->page('access.verify_phone_request', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Executes the confirm phone verification operation within the canonical Accessing component workflow.
     */
    public function confirmPhoneVerification(AccessEntity $user, FormView $form): AccessPageViewDTO
    {
        return $this->page('access.verify_phone_confirm', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Executes the second factor operation within the canonical Accessing component workflow.
     */
    public function secondFactor(
        AccessEntity $user,
        FormView $form,
        ?AccessSecondFactorEnrollmentDTO $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): AccessPageViewDTO {
        return $this->page('access.second_factor', [
            'user' => $user,
            'form' => $form,
            'enrollment' => $enrollment,
            'enabled' => $enabled,
            'showRecoveryCodes' => $showRecoveryCodes,
        ]);
    }

    /**
     * Executes the sessions operation within the canonical Accessing component workflow.
     */
    public function sessions(AccessEntity $user): AccessPageViewDTO
    {
        return $this->page('access.sessions', [
            'user' => $user,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function securityEvents(array $events): AccessPageViewDTO
    {
        return $this->page('access.security_event_index', [
            'events' => $events,
        ]);
    }

    /**
     * Executes the password operation within the canonical Accessing component workflow.
     */
    public function password(AccessEntity $user, FormView $form): AccessPageViewDTO
    {
        return $this->page('access.password', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @param array<int, mixed> $users
     */
    public function operatorUsers(array $users): AccessPageViewDTO
    {
        return $this->page('access.operator_index', [
            'users' => $users,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function operatorUserDetail(AccessEntity $user, array $events): AccessPageViewDTO
    {
        return $this->page('access.operator_detail', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function operatorSecurityEvents(array $events): AccessPageViewDTO
    {
        return $this->page('access.operator_security_event_index', [
            'events' => $events,
        ]);
    }

    /**
     * Executes the register operation within the canonical Accessing component workflow.
     */
    public function register(FormView $form, int $statusCode = 200): AccessPageViewDTO
    {
        return $this->page('access.register', [
            'form' => $form,
        ], $statusCode);
    }

    /**
     * Executes the sign in operation within the canonical Accessing component workflow.
     */
    public function signIn(FormView $form, int $statusCode = 200): AccessPageViewDTO
    {
        return $this->page('access.signin', [
            'form' => $form,
        ], $statusCode);
    }

    /**
     * Executes the second factor challenge operation within the canonical Accessing component workflow.
     */
    public function secondFactorChallenge(AccessEntity $user, FormView $form): AccessPageViewDTO
    {
        return $this->page('access.second_factor_challenge', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Executes the request recovery operation within the canonical Accessing component workflow.
     */
    public function requestRecovery(FormView $form): AccessPageViewDTO
    {
        return $this->page('access.recover_request', [
            'form' => $form,
        ]);
    }

    /**
     * Executes the reset recovery operation within the canonical Accessing component workflow.
     */
    public function resetRecovery(FormView $form): AccessPageViewDTO
    {
        return $this->page('access.recover_reset', [
            'form' => $form,
        ]);
    }

    /**
     * Executes the reset password request operation within the canonical Accessing component workflow.
     */
    public function resetPasswordRequest(FormView $form): AccessPageViewDTO
    {
        return $this->page('access.reset_password_request', [
            'request_form' => $form,
        ]);
    }

    /**
     * Executes the reset password check email operation within the canonical Accessing component workflow.
     */
    public function resetPasswordCheckEmail(): AccessPageViewDTO
    {
        return $this->page('access.reset_password_check_email');
    }

    /**
     * Executes the reset password operation within the canonical Accessing component workflow.
     */
    public function resetPassword(FormView $form): AccessPageViewDTO
    {
        return $this->page('access.reset_password_reset', [
            'reset_form' => $form,
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function page(string $view, array $parameters = [], int $statusCode = 200): AccessPageViewDTO
    {
        return new AccessPageViewDTO($view, $parameters, $statusCode);
    }
}
