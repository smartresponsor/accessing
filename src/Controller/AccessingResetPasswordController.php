<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\RepositoryInterface\AccountRepositoryInterface;
use App\ServiceInterface\SecurityEvent\AccessingSecurityEventRecorderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class AccessingResetPasswordController extends AbstractController
{
    private const RESET_PASSWORD_TOKEN_SESSION_KEY = 'accessing_reset_password_token';

    /**
     * Accept a password reset request and issue a reset token when account exists.
     */
    #[Route('/reset-password', name: 'accessing_reset_password_request', methods: ['GET', 'POST'])]
    public function request(
        Request $request,
        AccountRepositoryInterface $accountRepository,
        ResetPasswordHelperInterface $resetPasswordHelper,
        AccessingSecurityEventRecorderInterface $securityEventRecorder,
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();
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
                            'accessing_reset_password_reset',
                            ['token' => $resetToken->getToken()],
                            UrlGeneratorInterface::ABSOLUTE_URL,
                        )
                    ));
                } catch (ResetPasswordExceptionInterface) {
                    $this->addFlash('warning', 'A reset request could not be created right now.');
                }
            }

            return $this->redirectToRoute('accessing_reset_password_check_email');
        }

        return $this->render('accessing/reset_password/request.html.twig', [
            'request_form' => $form,
        ]);
    }

    #[Route('/reset-password/check-email', name: 'accessing_reset_password_check_email', methods: ['GET'])]
    public function checkEmail(): Response
    {
        return $this->render('accessing/reset_password/check_email.html.twig');
    }

    /**
     * Validate a reset token and update account password when submitted data is valid.
     */
    #[Route('/reset-password/reset', name: 'accessing_reset_password_reset_plain', methods: ['GET', 'POST'])]
    #[Route('/reset-password/reset/{token}', name: 'accessing_reset_password_reset', methods: ['GET', 'POST'])]
    public function reset(
        Request $request,
        ResetPasswordHelperInterface $resetPasswordHelper,
        UserPasswordHasherInterface $userPasswordHasher,
        AccountRepositoryInterface $accountRepository,
        AccessingSecurityEventRecorderInterface $securityEventRecorder,
        ?string $token = null,
    ): Response {
        $session = $request->getSession();

        if (null !== $token && '' !== trim($token)) {
            $session->set(self::RESET_PASSWORD_TOKEN_SESSION_KEY, trim($token));

            return $this->redirectToRoute('accessing_reset_password_reset_plain');
        }

        $token = (string) $session->get(self::RESET_PASSWORD_TOKEN_SESSION_KEY, '');
        if ('' === $token) {
            return $this->redirectToRoute('accessing_reset_password_request');
        }

        try {
            /** @var Account $account */
            $account = $resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface) {
            $session->remove(self::RESET_PASSWORD_TOKEN_SESSION_KEY);
            $this->addFlash('danger', 'Invalid or expired reset token.');

            return $this->redirectToRoute('accessing_reset_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = (string) $form->get('plainPassword')->getData();

            $resetPasswordHelper->removeResetRequest($token);
            $session->remove(self::RESET_PASSWORD_TOKEN_SESSION_KEY);
            $account->setPasswordHash($userPasswordHasher->hashPassword($account, $plainPassword));
            $accountRepository->save($account, true);

            $securityEventRecorder->record('reset_password.completed', $account, [
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
