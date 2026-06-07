<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Factory\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessUserEntity;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use Symfony\Component\Form\FormView;

final class AccessPageViewFactory implements AccessPageViewFactoryInterface
{
    /**
     * @param array<int, mixed> $events
     */
    public function home(AccessUserEntity $user, array $events): AccessPageView
    {
        return $this->page('user.overview', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function overview(AccessUserEntity $user, array $events): AccessPageView
    {
        return $this->page('user.overview', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    public function verifyEmail(AccessUserEntity $user, FormView $form): AccessPageView
    {
        return $this->page('user.verify_email', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    public function requestPhoneVerification(AccessUserEntity $user, FormView $form): AccessPageView
    {
        return $this->page('user.verify_phone_request', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    public function confirmPhoneVerification(AccessUserEntity $user, FormView $form): AccessPageView
    {
        return $this->page('user.verify_phone_confirm', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    public function secondFactor(
        AccessUserEntity $user,
        FormView $form,
        ?AccessSecondFactorEnrollmentDto $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): AccessPageView {
        return $this->page('user.second_factor', [
            'user' => $user,
            'form' => $form,
            'enrollment' => $enrollment,
            'enabled' => $enabled,
            'showRecoveryCodes' => $showRecoveryCodes,
        ]);
    }

    public function sessions(AccessUserEntity $user): AccessPageView
    {
        return $this->page('user.sessions', [
            'user' => $user,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function securityEvents(array $events): AccessPageView
    {
        return $this->page('security_event.index', [
            'events' => $events,
        ]);
    }

    public function password(AccessUserEntity $user, FormView $form): AccessPageView
    {
        return $this->page('user.password', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * @param array<int, mixed> $users
     */
    public function operatorUsers(array $users): AccessPageView
    {
        return $this->page('user.operator_index', [
            'users' => $users,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function operatorUserDetail(AccessUserEntity $user, array $events): AccessPageView
    {
        return $this->page('user.operator_detail', [
            'user' => $user,
            'events' => $events,
        ]);
    }

    /**
     * @param array<int, mixed> $events
     */
    public function operatorSecurityEvents(array $events): AccessPageView
    {
        return $this->page('security_event.operator_index', [
            'events' => $events,
        ]);
    }

    public function register(FormView $form): AccessPageView
    {
        return $this->page('user.register', [
            'form' => $form,
        ]);
    }

    public function signIn(FormView $form): AccessPageView
    {
        return $this->page('user.sign_in', [
            'form' => $form,
        ]);
    }

    public function secondFactorChallenge(AccessUserEntity $user, FormView $form): AccessPageView
    {
        return $this->page('user.second_factor_challenge', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    public function requestRecovery(FormView $form): AccessPageView
    {
        return $this->page('user.recover_request', [
            'form' => $form,
        ]);
    }

    public function resetRecovery(FormView $form): AccessPageView
    {
        return $this->page('user.recover_reset', [
            'form' => $form,
        ]);
    }

    public function resetPasswordRequest(FormView $form): AccessPageView
    {
        return $this->page('reset_password.request', [
            'request_form' => $form,
        ]);
    }

    public function resetPasswordCheckEmail(): AccessPageView
    {
        return $this->page('reset_password.check_email');
    }

    public function resetPassword(FormView $form): AccessPageView
    {
        return $this->page('reset_password.reset', [
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
