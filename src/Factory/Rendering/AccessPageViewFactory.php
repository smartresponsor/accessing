<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Factory\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Dto\AccessSecondFactorEnrollment;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use Symfony\Component\Form\FormView;

final class AccessPageViewFactory implements AccessPageViewFactoryInterface
{
    /**
     * @param array<int, mixed> $events
     */
    public function home(AccessEntity $user, array $events): AccessPageView
    {
        return $this->page('access.overview', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function overview(AccessEntity $user, array $events): AccessPageView
    {
        return $this->page('access.overview', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    public function verifyEmail(AccessEntity $user, FormView $form): AccessPageView
    {
        return $this->page('access.verify_email', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    public function requestPhoneVerification(AccessEntity $user, FormView $form): AccessPageView
    {
        return $this->page('access.verify_phone_request', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    public function confirmPhoneVerification(AccessEntity $user, FormView $form): AccessPageView
    {
        return $this->page('access.verify_phone_confirm', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    public function secondFactor(
        AccessEntity $user,
        FormView $form,
        ?AccessSecondFactorEnrollment $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): AccessPageView {
        return $this->page('access.second_factor', [
            'user' => $user,
            'form' => $form,
            'enrollment' => $enrollment,
            'enabled' => $enabled,
            'showRecoveryCodes' => $showRecoveryCodes,
        ]);
    }

    public function sessions(AccessEntity $user): AccessPageView
    {
        return $this->page('access.sessions', [
            'user' => $user,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function securityEvents(array $events): AccessPageView
    {
        return $this->page('access.security_event_index', [
            'events' => $events,
        ]);
    }

    public function password(AccessEntity $user, FormView $form): AccessPageView
    {
        return $this->page('access.password', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @param array<int, mixed> $users
     */
    public function operatorUsers(array $users): AccessPageView
    {
        return $this->page('access.operator_index', [
            'users' => $users,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function operatorUserDetail(AccessEntity $user, array $events): AccessPageView
    {
        return $this->page('access.operator_detail', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function operatorSecurityEvents(array $events): AccessPageView
    {
        return $this->page('access.operator_security_event_index', [
            'events' => $events,
        ]);
    }

    public function register(FormView $form, int $statusCode = 200): AccessPageView
    {
        return $this->page('access.register', [
            'form' => $form,
        ], $statusCode);
    }

    public function signIn(FormView $form, int $statusCode = 200): AccessPageView
    {
        return $this->page('access.signin', [
            'form' => $form,
        ], $statusCode);
    }

    public function secondFactorChallenge(AccessEntity $user, FormView $form): AccessPageView
    {
        return $this->page('access.second_factor_challenge', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    public function requestRecovery(FormView $form): AccessPageView
    {
        return $this->page('access.recover_request', [
            'form' => $form,
        ]);
    }

    public function resetRecovery(FormView $form): AccessPageView
    {
        return $this->page('access.recover_reset', [
            'form' => $form,
        ]);
    }

    public function resetPasswordRequest(FormView $form): AccessPageView
    {
        return $this->page('access.reset_password_request', [
            'request_form' => $form,
        ]);
    }

    public function resetPasswordCheckEmail(): AccessPageView
    {
        return $this->page('access.reset_password_check_email');
    }

    public function resetPassword(FormView $form): AccessPageView
    {
        return $this->page('access.reset_password_reset', [
            'reset_form' => $form,
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function page(string $view, array $parameters = [], int $statusCode = 200): AccessPageView
    {
        return new AccessPageView($view, $parameters, $statusCode);
    }
}
