<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Controller;

use App\Accessing\Entity\Account;
use App\Accessing\Form\ChangePasswordFormType;
use App\Accessing\Form\ResetPasswordRequestFormType;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class AccessingResetPasswordController extends AbstractController
{
    private const string RESET_PASSWORD_TOKEN_SESSION_KEY = 'accessing_reset_password_token';

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly AccountRepositoryInterface $accountRepository,
        private readonly AccessingSecurityEventRecorderInterface $securityEventRecorder,
    ) {
    }

    /**
     * Accept a password reset request and issue a reset token when account exists.
     */
    #[Route('/forgot-password', name: 'accessing_forgot_password', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        AccountRepositoryInterface $accountRepository,
        ResetPasswordHelperInterface $resetPasswordHelper,
        AccessingSecurityEventRecorderInterface $securityEventRecorder,
        RateLimiterFactory $accessingForgotPasswordLimiter,
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailData = $form->get('email')->getData();
            $email = is_string($emailData) ? $emailData : '';
            $limiter = $accessingForgotPasswordLimiter->create(sprintf('%s|%s', strtolower(trim($email)), $request->getClientIp() ?? 'unknown'));

            if (!$limiter->consume()->isAccepted()) {
                $this->addFlash('danger', 'Too many forgot-password requests. Please wait before trying again.');

                return $this->redirectToRoute('accessing_forgot_password');
            }
            $account = $accountRepository->findOneByEmailAddress($email);

            if ($account instanceof Account) {
                try {
                    $resetToken = $resetPasswordHelper->generateResetToken($account);

                    $securityEventRecorder->record('reset_password.requested', $account, [
                        'email' => $account->getEmail(),
                    ]);

                    $this->addFlash('info', sprintf(
                        'Owner-oriented preview link: %s',
                        $this->generateUrl(
                            'accessing_forgot_password_reset',
                            ['token' => $resetToken->getToken()],
                            UrlGeneratorInterface::ABSOLUTE_URL,
                        )
                    ));
                } catch (ResetPasswordExceptionInterface) {
                    $this->addFlash('warning', 'A reset request could not be created right now.');
                }
            }

            return $this->redirectToRoute('accessing_forgot_password_check_email');
        }

        return $this->render('accessing/reset_password/request.html.twig', [
            'request_form' => $form,
        ]);
    }

    #[Route('/forgot-password/check-email', name: 'accessing_forgot_password_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('accessing/reset_password/check_email.html.twig');
    }

    /**
     * Validate a reset token and update account password when submitted data is valid.
     */
    #[Route('/forgot-password/reset', name: 'accessing_forgot_password_reset_plain', methods: ['GET', 'POST'])]
    #[Route('/forgot-password/reset/{token}', name: 'accessing_forgot_password_reset', methods: ['GET', 'POST'])]
    public function reset(
        Request $request,
        ?string $token = null,
    ): Response {
        $session = $request->getSession();

        if (null !== $token && '' !== trim($token)) {
            $session->set(self::RESET_PASSWORD_TOKEN_SESSION_KEY, trim($token));

            return $this->redirectToRoute('accessing_forgot_password_reset_plain');
        }

        $tokenData = $session->get(self::RESET_PASSWORD_TOKEN_SESSION_KEY, '');
        $token = is_string($tokenData) ? $tokenData : '';
        if ('' === $token) {
            return $this->redirectToRoute('accessing_forgot_password');
        }

        try {
            /** @var Account $account */
            $account = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            $session->remove(self::RESET_PASSWORD_TOKEN_SESSION_KEY);
            $this->addFlash('danger', 'Invalid or expired reset token.');

            return $this->redirectToRoute('accessing_forgot_password');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
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

            $this->addFlash('success', 'Password changed successfully.');

            return $this->redirectToRoute('accessing_sign_in');
        }

        return $this->render('accessing/reset_password/reset.html.twig', [
            'reset_form' => $form,
        ]);
    }
}
