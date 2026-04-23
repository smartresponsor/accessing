<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Rendering;

use App\Accessing\Dto\AccessingSecondFactorEnrollmentDto;
use App\Accessing\Dto\PageView;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\ServiceInterface\Rendering\PageViewFactoryInterface;
use Symfony\Component\Form\FormView;

final class PageViewFactory implements PageViewFactoryInterface
{
    public function home(AccessAccountEntity $account, array $events): PageView
    {
        return $this->page('account.overview', [
            'account' => $account,
            'events' => $events,
        ]);
    }

    public function overview(AccessAccountEntity $account, array $events): PageView
    {
        return $this->page('account.overview', [
            'account' => $account,
            'events' => $events,
        ]);
    }

    public function verifyEmail(AccessAccountEntity $account, FormView $form): PageView
    {
        return $this->page('account.verify_email', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function requestPhoneVerification(AccessAccountEntity $account, FormView $form): PageView
    {
        return $this->page('account.verify_phone_request', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function confirmPhoneVerification(AccessAccountEntity $account, FormView $form): PageView
    {
        return $this->page('account.verify_phone_confirm', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function secondFactor(
        AccessAccountEntity $account,
        FormView $form,
        ?AccessingSecondFactorEnrollmentDto $enrollment,
        bool $enabled,
        bool $showRecoveryCodes,
    ): PageView {
        return $this->page('account.second_factor', [
            'account' => $account,
            'form' => $form,
            'enrollment' => $enrollment,
            'enabled' => $enabled,
            'showRecoveryCodes' => $showRecoveryCodes,
        ]);
    }

    public function sessions(AccessAccountEntity $account): PageView
    {
        return $this->page('account.sessions', [
            'account' => $account,
        ]);
    }

    public function securityEvents(array $events): PageView
    {
        return $this->page('security_event.index', [
            'events' => $events,
        ]);
    }

    public function password(AccessAccountEntity $account, FormView $form): PageView
    {
        return $this->page('account.password', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function operatorAccounts(array $accounts): PageView
    {
        return $this->page('account.operator_index', [
            'accounts' => $accounts,
        ]);
    }

    public function operatorAccountDetail(AccessAccountEntity $account, array $events): PageView
    {
        return $this->page('account.operator_detail', [
            'account' => $account,
            'events' => $events,
        ]);
    }

    public function operatorSecurityEvents(array $events): PageView
    {
        return $this->page('security_event.operator_index', [
            'events' => $events,
        ]);
    }

    public function register(FormView $form): PageView
    {
        return $this->page('account.register', [
            'form' => $form,
        ]);
    }

    public function signIn(FormView $form): PageView
    {
        return $this->page('account.sign_in', [
            'form' => $form,
        ]);
    }

    public function secondFactorChallenge(AccessAccountEntity $account, FormView $form): PageView
    {
        return $this->page('account.second_factor_challenge', [
            'account' => $account,
            'form' => $form,
        ]);
    }

    public function requestRecovery(FormView $form): PageView
    {
        return $this->page('account.recover_request', [
            'form' => $form,
        ]);
    }

    public function resetRecovery(FormView $form): PageView
    {
        return $this->page('account.recover_reset', [
            'form' => $form,
        ]);
    }

    public function resetPasswordRequest(FormView $form): PageView
    {
        return $this->page('reset_password.request', [
            'request_form' => $form,
        ]);
    }

    public function resetPasswordCheckEmail(): PageView
    {
        return $this->page('reset_password.check_email');
    }

    public function resetPassword(FormView $form): PageView
    {
        return $this->page('reset_password.reset', [
            'reset_form' => $form,
        ]);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function page(string $view, array $parameters = [], int $statusCode = 200): PageView
    {
        return new PageView($view, $parameters, $statusCode);
    }
}
