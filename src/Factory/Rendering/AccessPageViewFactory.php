<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Factory\Rendering;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Dto\AccessSecondFactorEnrollmentDto;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use Symfony\Component\Form\FormView;

final class AccessPageViewFactory implements AccessPageViewFactoryInterface
{
    public function home(AccessAccountEntity $account, array $events): AccessPageView
    {
        return $this->page('account.overview', [
            'account' => $account,
            'events' => $events,
        ]);
    }

    public function overview(AccessAccountEntity $account, array $events): AccessPageView
    {
        return $this->page('account.overview', [
            'account' => $account,
            'events' => $events,
        ]);
    }

    public function verifyEmail(AccessAccountEntity $account, FormView $form): AccessPageView
    {
        return $this->page('account.verify_email', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function requestPhoneVerification(AccessAccountEntity $account, FormView $form): AccessPageView
    {
        return $this->page('account.verify_phone_request', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function confirmPhoneVerification(AccessAccountEntity $account, FormView $form): AccessPageView
    {
        return $this->page('account.verify_phone_confirm', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function secondFactor(
        AccessAccountEntity $account,
        FormView $form,
        ?AccessSecondFactorEnrollmentDto $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): AccessPageView {
        return $this->page('account.second_factor', [
            'account' => $account,
            'form' => $form,
            'enrollment' => $enrollment,
            'enabled' => $enabled,
            'showRecoveryCodes' => $showRecoveryCodes,
        ]);
    }

    public function sessions(AccessAccountEntity $account): AccessPageView
    {
        return $this->page('account.sessions', [
            'account' => $account,
        ]);
    }

    public function securityEvents(array $events): AccessPageView
    {
        return $this->page('security_event.index', [
            'events' => $events,
        ]);
    }

    public function password(AccessAccountEntity $account, FormView $form): AccessPageView
    {
        return $this->page('account.password', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function operatorAccounts(array $accounts): AccessPageView
    {
        return $this->page('account.operator_index', [
            'accounts' => $accounts,
        ]);
    }

    public function operatorAccountDetail(AccessAccountEntity $account, array $events): AccessPageView
    {
        return $this->page('account.operator_detail', [
            'account' => $account,
            'events' => $events,
        ]);
    }

    public function operatorSecurityEvents(array $events): AccessPageView
    {
        return $this->page('security_event.operator_index', [
            'events' => $events,
        ]);
    }

    public function register(FormView $form): AccessPageView
    {
        return $this->page('account.register', [
            'form' => $form,
        ]);
    }

    public function signIn(FormView $form): AccessPageView
    {
        return $this->page('account.sign_in', [
            'form' => $form,
        ]);
    }

    public function secondFactorChallenge(AccessAccountEntity $account, FormView $form): AccessPageView
    {
        return $this->page('account.second_factor_challenge', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function requestRecovery(FormView $form): AccessPageView
    {
        return $this->page('account.recover_request', [
            'form' => $form,
        ]);
    }

    public function resetRecovery(FormView $form): AccessPageView
    {
        return $this->page('account.recover_reset', [
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
