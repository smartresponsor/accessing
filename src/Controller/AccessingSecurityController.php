<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Controller;

use App\Accessing\Dto\AccountRegistrationRequest;
use App\Accessing\Dto\AccountSignInRequestDto;
use App\Accessing\Dto\RecoveryRequestDto;
use App\Accessing\Dto\RecoveryResetDto;
use App\Accessing\Dto\VerificationCodeDto;
use App\Accessing\Entity\Account;
use App\Accessing\Form\AccountRegistrationFormType;
use App\Accessing\Form\AccountSignInFormType;
use App\Accessing\Form\RecoveryRequestFormType;
use App\Accessing\Form\RecoveryResetFormType;
use App\Accessing\Form\VerificationCodeFormType;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\ServiceInterface\Account\AccessingAccountAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessingRecoveryServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessingSecondFactorServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingSecurityController extends AbstractController
{
    use AccessingDemoCodeFlashTrait;

    /**
     * Render and process account registration.
     */
    #[Route('/sign-up', name: 'accessing_sign_up', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        AccessingAccountRegistrationServiceInterface $accountRegistrationService,
        RateLimiterFactory $accessingSignUpLimiter,
    ): Response {
        if ($this->getUser() instanceof Account) {
            return $this->redirectToRoute('accessing_overview');
        }

        $form = $this->createForm(AccountRegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccountRegistrationRequest $data */
            $data = $form->getData();

            $limiter = $accessingSignUpLimiter->create(sprintf('%s|%s', strtolower(trim($data->email)), $request->getClientIp() ?? 'unknown'));

            if (!$limiter->consume()->isAccepted()) {
                $this->addFlash('danger', 'Too many sign-up attempts. Please wait before trying again.');

                return $this->redirectToRoute('accessing_sign_up');
            }

            try {
                $accountRegistrationService->register($data);
                $this->addFlash('success', 'Registration complete. Verify your email address to finish activation.');

                return $this->redirectToRoute('accessing_sign_in');
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->render('accessing/account/sign_up.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Render and process canonical sign-in.
     */
    #[Route('/sign-in', name: 'accessing_sign_in', methods: ['GET', 'POST'])]
    public function signIn(
        Request $request,
        AccessingAccountAuthenticationServiceInterface $accountAuthenticationService,
    ): Response {
        if ($this->getUser() instanceof Account) {
            return $this->redirectToRoute('accessing_overview');
        }

        $form = $this->createForm(AccountSignInFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccountSignInRequestDto $data */
            $data = $form->getData();
            $result = $accountAuthenticationService->attemptPasswordSignIn(
                $data->emailAddress,
                $data->plainPassword,
                $request,
            );

            if ($result->authenticated) {
                return $this->redirectToRoute('accessing_overview');
            }

            if ($result->requiresSecondFactor) {
                $this->addFlash('info', 'Enter your authenticator or recovery code to finish signing in.');

                return $this->redirectToRoute('accessing_sign_in_second_factor');
            }

            $this->addFlash('danger', $result->message);
        }

        return $this->render('accessing/account/sign_in.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Complete second-factor challenge for a pending sign-in attempt.
     */
    #[Route('/sign-in/second-factor', name: 'accessing_sign_in_second_factor', methods: ['GET', 'POST'])]
    public function secondFactorChallenge(
        Request $request,
        AccountRepositoryInterface $accountRepository,
        AccessingAccountAuthenticationServiceInterface $accountAuthenticationService,
        AccessingSecondFactorServiceInterface $secondFactorService,
    ): Response {
        $pendingAccountId = $accountAuthenticationService->getPendingSecondFactorAccountId($request->getSession());

        if (null === $pendingAccountId) {
            return $this->redirectToRoute('accessing_sign_in');
        }

        $account = $accountRepository->findById($pendingAccountId);

        if (!$account instanceof Account) {
            $accountAuthenticationService->clearPendingSecondFactor($request->getSession());

            return $this->redirectToRoute('accessing_sign_in');
        }

        $form = $this->createForm(VerificationCodeFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var VerificationCodeDto $data */
            $data = $form->getData();

            if ($secondFactorService->verifyChallenge($account, $data->code)) {
                $accountAuthenticationService->completePendingSecondFactor($account, $request);
                $this->addFlash('success', 'Signed in successfully.');

                return $this->redirectToRoute('accessing_overview');
            }

            $this->addFlash('danger', 'The second factor code was not accepted.');
        }

        return $this->render('accessing/account/second_factor_challenge.html.twig', [
            'account' => $account,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Sign out current account and invalidate current session.
     */
    #[Route('/sign-out', name: 'accessing_sign_out', methods: ['POST'])]
    public function signOut(
        Request $request,
        AccessingAccountAuthenticationServiceInterface $accountAuthenticationService,
    ): Response {
        $accountAuthenticationService->signOut(
            $this->getUser() instanceof Account ? $this->getUser() : null,
            $request,
        );

        return $this->redirectToRoute('accessing_sign_in');
    }

    /**
     * Request password recovery challenge by email address.
     */
    #[Route('/recover', name: 'accessing_recover', methods: ['GET', 'POST'])]
    public function requestRecovery(
        Request $request,
        AccessingRecoveryServiceInterface $recoveryService,
        RateLimiterFactory $accessingRecoveryLimiter,
    ): Response {
        $form = $this->createForm(RecoveryRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RecoveryRequestDto $data */
            $data = $form->getData();
            $limiter = $accessingRecoveryLimiter->create(sprintf('%s|%s', strtolower(trim($data->emailAddress)), $request->getClientIp() ?? 'unknown'));

            if (!$limiter->consume()->isAccepted()) {
                $this->addFlash('danger', 'Too many recovery requests. Please wait before trying again.');

                return $this->redirectToRoute('accessing_recover');
            }
            $issuedChallenge = $recoveryService->requestPasswordRecovery($data->emailAddress, $request);
            $this->addFlash('info', 'If an account exists, a password recovery code has been issued.');

            if (null !== $issuedChallenge) {
                $this->addDemoCodeFlash('Password recovery code', $issuedChallenge->plainCode);
            }

            return $this->redirectToRoute('accessing_recover_reset');
        }

        return $this->render('accessing/account/recover.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Reset password using recovery code and email address.
     */
    #[Route('/recover/reset', name: 'accessing_recover_reset', methods: ['GET', 'POST'])]
    public function resetRecovery(
        Request $request,
        AccessingRecoveryServiceInterface $recoveryService,
    ): Response {
        $form = $this->createForm(RecoveryResetFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RecoveryResetDto $data */
            $data = $form->getData();

            if ($recoveryService->resetPassword(
                $data->emailAddress,
                $data->code,
                $data->newPassword,
            )) {
                $this->addFlash('success', 'Password recovery completed. You can now sign in.');

                return $this->redirectToRoute('accessing_sign_in');
            }

            $this->addFlash('danger', 'Password recovery failed. Check the email address and recovery code.');
        }

        return $this->render('accessing/account/recover_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
