<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Controller;

use App\Accessing\Dto\PasswordChangeDto;
use App\Accessing\Dto\PhoneVerificationRequestDto;
use App\Accessing\Dto\VerificationCodeDto;
use App\Accessing\Entity\Account;
use App\Accessing\Form\PasswordChangeFormType;
use App\Accessing\Form\PhoneVerificationRequestFormType;
use App\Accessing\Form\VerificationCodeFormType;
use App\Accessing\RepositoryInterface\SecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\AccountSession\AccessingAccountSessionServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessingCredentialServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessingSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessingVerificationChallengeServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AccessingController extends AbstractController
{
    use AccessingDemoCodeFlashTrait;

    /**
     * Render home entrypoint for signed-in accounts and redirect guests to sign-in.
     */
    #[Route('/', name: 'accessing_home', methods: ['GET'])]
    public function home(SecurityEventRepositoryInterface $securityEventRepository): Response
    {
        if (!$this->getUser() instanceof Account) {
            return $this->redirectToRoute('accessing_sign_in');
        }

        return $this->render('accessing/account/overview.html.twig', [
            'account' => $this->requireAccount(),
            'events' => $securityEventRepository->findRecentEventsForAccount($this->requireAccount(), 8),
        ]);
    }

    /**
     * Render account overview with recent security events.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/overview', name: 'accessing_overview', methods: ['GET'])]
    public function overview(SecurityEventRepositoryInterface $securityEventRepository): Response
    {
        return $this->render('accessing/account/overview.html.twig', [
            'account' => $this->requireAccount(),
            'events' => $securityEventRepository->findRecentEventsForAccount($this->requireAccount(), 8),
        ]);
    }

    /**
     * Verify account email ownership using a challenge code.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/verify/email', name: 'accessing_verify_email', methods: ['GET', 'POST'])]
    public function verifyEmail(
        Request $request,
        AccessingVerificationChallengeServiceInterface $verificationChallengeService,
    ): Response {
        $account = $this->requireAccount();
        $form = $this->createForm(VerificationCodeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var VerificationCodeDto $data */
            $data = $form->getData();

            if ($verificationChallengeService->completeEmailVerification($account, $data->code)) {
                $this->addFlash('success', 'Email verification completed.');

                return $this->redirectToRoute('accessing_overview');
            }

            $this->addFlash('danger', 'That email verification code is invalid or expired.');
        }

        return $this->render('accessing/account/verify_email.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Issue a fresh email verification challenge.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/verify/email/resend', name: 'accessing_verify_email_resend', methods: ['POST'])]
    public function resendEmailVerification(
        Request $request,
        AccessingVerificationChallengeServiceInterface $verificationChallengeService,
        RateLimiterFactory $accessingVerificationResendLimiter,
    ): Response {
        $account = $this->requireAccount();
        $limiter = $accessingVerificationResendLimiter->create(sprintf('%d|%s', $account->getId() ?? 0, $request->getClientIp() ?? 'unknown'));

        if (!$limiter->consume()->isAccepted()) {
            $this->addFlash('danger', 'Too many verification resend requests. Please wait before trying again.');

            return $this->redirectToRoute('accessing_verify_email');
        }

        $issuedChallenge = $verificationChallengeService->issueEmailVerification($account, $request);
        $this->addFlash('info', 'A fresh email verification code has been issued.');
        $this->addDemoCodeFlash('Email verification code', $issuedChallenge->plainCode);

        return $this->redirectToRoute('accessing_verify_email');
    }

    /**
     * Start phone verification challenge issuance.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/verify/phone', name: 'accessing_verify_phone', methods: ['GET', 'POST'])]
    public function requestPhoneVerification(
        Request $request,
        AccessingVerificationChallengeServiceInterface $verificationChallengeService,
        RateLimiterFactory $accessingVerificationResendLimiter,
    ): Response {
        $account = $this->requireAccount();
        $form = $this->createForm(PhoneVerificationRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PhoneVerificationRequestDto $data */
            $data = $form->getData();
            $limiter = $accessingVerificationResendLimiter->create(sprintf('%d|%s|%s', $account->getId() ?? 0, trim($data->phoneNumber), $request->getClientIp() ?? 'unknown'));

            if (!$limiter->consume()->isAccepted()) {
                $this->addFlash('danger', 'Too many phone verification requests. Please wait before trying again.');

                return $this->redirectToRoute('accessing_verify_phone');
            }

            $issuedChallenge = $verificationChallengeService->issuePhoneVerification($account, $data->phoneNumber, $request);
            $this->addFlash('info', 'Phone verification code sent.');
            $this->addDemoCodeFlash('Phone verification code', $issuedChallenge->plainCode);

            return $this->redirectToRoute('accessing_verify_phone_confirm');
        }

        return $this->render('accessing/account/verify_phone_request.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Confirm phone verification with challenge code.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/verify/phone/confirm', name: 'accessing_verify_phone_confirm', methods: ['GET', 'POST'])]
    public function confirmPhoneVerification(
        Request $request,
        AccessingVerificationChallengeServiceInterface $verificationChallengeService,
    ): Response {
        $account = $this->requireAccount();
        $form = $this->createForm(VerificationCodeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var VerificationCodeDto $data */
            $data = $form->getData();

            if ($verificationChallengeService->completePhoneVerification($account, $data->code)) {
                $this->addFlash('success', 'Phone verification completed.');

                return $this->redirectToRoute('accessing_overview');
            }

            $this->addFlash('danger', 'That phone verification code is invalid or expired.');
        }

        return $this->render('accessing/account/verify_phone_confirm.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Render and process second-factor enrollment confirmation.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/second-factor', name: 'accessing_second_factor', methods: ['GET', 'POST'])]
    public function secondFactor(
        Request $request,
        AccessingSecondFactorServiceInterface $secondFactorService,
    ): Response {
        $account = $this->requireAccount();
        $enrollment = $account->getSecondFactor()?->isEnabled() ? null : $secondFactorService->beginEnrollment($account);
        $form = $this->createForm(VerificationCodeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var VerificationCodeDto $data */
            $data = $form->getData();
            $confirmedEnrollment = $secondFactorService->confirmEnrollment($account, $data->code);

            if (null !== $confirmedEnrollment) {
                $this->addFlash('success', 'Second factor is now enabled.');
                $this->addFlash('warning', 'Save the recovery codes shown on the page now. They will not be shown again.');

                return $this->render('accessing/account/second_factor.html.twig', [
                    'account' => $account,
                    'form' => $form->createView(),
                    'enrollment' => $confirmedEnrollment,
                    'enabled' => true,
                    'showRecoveryCodes' => true,
                ]);
            }

            $this->addFlash('danger', 'That authenticator code was not accepted.');
        }

        return $this->render('accessing/account/second_factor.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
            'enrollment' => $enrollment,
            'enabled' => $account->getSecondFactor()?->isEnabled() ?? false,
            'showRecoveryCodes' => false,
        ]);
    }

    /**
     * Disable second-factor enrollment and recovery codes.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/second-factor/disable', name: 'accessing_second_factor_disable', methods: ['POST'])]
    public function disableSecondFactor(
        AccessingSecondFactorServiceInterface $secondFactorService,
    ): Response {
        $secondFactorService->disableSecondFactor($this->requireAccount());
        $this->addFlash('info', 'Second factor has been disabled.');

        return $this->redirectToRoute('accessing_second_factor');
    }

    /**
     * Render session management page.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/sessions', name: 'accessing_sessions', methods: ['GET'])]
    public function sessions(): Response
    {
        return $this->render('accessing/account/sessions.html.twig', [
            'account' => $this->requireAccount(),
        ]);
    }

    /**
     * Invalidate all active sessions except current one.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/sessions/others/invalidate', name: 'accessing_sessions_invalidate_others', methods: ['POST'])]
    public function invalidateOtherSessions(
        Request $request,
        AccessingAccountSessionServiceInterface $accountSessionService,
    ): Response {
        $invalidatedCount = $accountSessionService->invalidateOtherSessions($this->requireAccount(), $request->getSession());
        $this->addFlash('info', sprintf('%d other session(s) invalidated.', $invalidatedCount));

        return $this->redirectToRoute('accessing_sessions');
    }

    /**
     * Render recent security events for current account.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/security-events', name: 'accessing_security_events', methods: ['GET'])]
    public function securityEvents(SecurityEventRepositoryInterface $securityEventRepository): Response
    {
        $account = $this->requireAccount();

        return $this->render('accessing/security_event/index.html.twig', [
            'account' => $account,
            'events' => $securityEventRepository->findRecentEventsForAccount($account),
        ]);
    }

    /**
     * Change account password after current-password verification.
     */
    #[IsGranted('ROLE_ACCOUNT')]
    #[Route('/account/password', name: 'accessing_account_password', methods: ['GET', 'POST'])]
    public function password(
        Request $request,
        AccessingCredentialServiceInterface $credentialService,
    ): Response {
        $account = $this->requireAccount();
        $form = $this->createForm(PasswordChangeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var PasswordChangeDto $data */
            $data = $form->getData();

            if (!$credentialService->verifyPassword($account, $data->currentPassword)) {
                $this->addFlash('danger', 'The current password is incorrect.');
            } else {
                $credentialService->changePassword($account, $data->newPassword);
                $this->addFlash('success', 'Password updated.');

                return $this->redirectToRoute('accessing_overview');
            }
        }

        return $this->render('accessing/account/password.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
        ]);
    }

    private function requireAccount(): Account
    {
        $account = $this->getUser();

        if (!$account instanceof Account) {
            throw $this->createAccessDeniedException();
        }

        return $account;
    }
}
