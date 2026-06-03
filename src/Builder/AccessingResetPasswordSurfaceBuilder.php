<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Form\ChangePasswordType;
use App\Accessing\Form\ResetPasswordRequestType;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\ServiceInterface\Rendering\PageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\PageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use App\Interfacing\ServiceInterface\Presentation\SurfaceRenderableInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final readonly class AccessingResetPasswordSurfaceBuilder
{
    private const string RESET_PASSWORD_TOKEN_SESSION_KEY = 'accessing_reset_password_token';

    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private UserPasswordHasherInterface $userPasswordHasher,
        private AccountRepositoryInterface $accountRepository,
        private AccessingSecurityEventRecorderInterface $securityEventRecorder,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Accept a password reset request and issue a reset token when account exists.
     */
    public function request(
        Request $request,
        AccountRepositoryInterface $accountRepository,
        ResetPasswordHelperInterface $resetPasswordHelper,
        AccessingSecurityEventRecorderInterface $securityEventRecorder,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $form = $this->formFactory->create(ResetPasswordRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailData = $form->get('email')->getData();
            $email = is_string($emailData) ? $emailData : '';
            $account = $accountRepository->findOneByEmailAddress($email);

            if ($account instanceof AccessAccountEntity) {
                try {
                    $resetToken = $resetPasswordHelper->generateResetToken($account);

                    $securityEventRecorder->record('reset_password.requested', $account, [
                        'email' => $account->getEmail(),
                    ]);

                    $this->flash($request, 'info', sprintf(
                        'Owner-oriented preview link: %s',
                        $this->urlGenerator->generate(
                            'accessing_reset_password_reset',
                            ['token' => $resetToken->getToken()],
                            UrlGeneratorInterface::ABSOLUTE_URL,
                        )
                    ));
                } catch (ResetPasswordExceptionInterface) {
                    $this->flash($request, 'warning', 'A reset request could not be created right now.');
                }
            }

            return $this->redirectTo('accessing_forgot_password_check_email');
        }

        return $pageResponder->respond($pageViewFactory->resetPasswordRequest($form->createView()));
    }

    public function checkEmail(
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->resetPasswordCheckEmail());
    }

    /**
     * Validate a reset token and update account password when submitted data is valid.
     */
    public function reset(
        Request $request,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
        ?string $token = null,
    ): Response|SurfaceRenderableInterface {
        $session = $request->getSession();

        if (null !== $token && '' !== trim($token)) {
            $session->set(self::RESET_PASSWORD_TOKEN_SESSION_KEY, trim($token));

            return $this->redirectTo('accessing_forgot_password_reset_plain');
        }

        $tokenData = $session->get(self::RESET_PASSWORD_TOKEN_SESSION_KEY, '');
        $token = is_string($tokenData) ? $tokenData : '';
        if ('' === $token) {
            return $this->redirectTo('accessing_forgot_password');
        }

        $form = $this->formFactory->create(ChangePasswordType::class);
        if ('GET' === $request->getMethod()) {
            return $pageResponder->respond($pageViewFactory->resetPassword($form->createView()));
        }

        try {
            /** @var AccessAccountEntity $account */
            $account = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            $session->remove(self::RESET_PASSWORD_TOKEN_SESSION_KEY);
            $this->flash($request, 'danger', 'Invalid or expired reset token.');

            return $this->redirectTo('accessing_forgot_password');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPasswordData = $form->get('plainPassword')->getData();
            $plainPassword = is_string($plainPasswordData) ? $plainPasswordData : '';

            $this->resetPasswordHelper->removeResetRequest($token);
            $session->remove(self::RESET_PASSWORD_TOKEN_SESSION_KEY);
            $account->setPasswordHash($this->userPasswordHasher->hashPassword($account, $plainPassword));
            $this->accountRepository->save($account, true);

            $this->securityEventRecorder->record('reset_password.completed', $account, [
                'email' => $account->getEmail(),
            ]);

            $this->flash($request, 'success', 'Password changed successfully.');

            return $this->redirectTo('interfacing_welcome_sign_in');
        }

        return $pageResponder->respond($pageViewFactory->resetPassword($form->createView()));
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $request->getSession()->getFlashBag()->add($type, $message);
    }

    private function redirectTo(string $route, array $parameters = [], int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters), $status);
    }
}
