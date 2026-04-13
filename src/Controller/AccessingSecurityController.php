<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Account;
use App\Form\AccountRegistrationFormType;
use App\Form\AccountSignInFormType;
use App\Form\RecoveryRequestFormType;
use App\Form\RecoveryResetFormType;
use App\Form\VerificationCodeFormType;
use App\RepositoryInterface\AccountRepositoryInterface;
use App\ServiceInterface\Account\AccessingAccountAuthenticationServiceInterface;
use App\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\ServiceInterface\Recovery\AccessingRecoveryServiceInterface;
use App\ServiceInterface\SecondFactor\AccessingSecondFactorServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccessingSecurityController extends AbstractController
{
    #[Route('/register', name: 'accessing_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        AccessingAccountRegistrationServiceInterface $accountRegistrationService,
    ): Response {
        if ($this->getUser() instanceof Account) {
            return $this->redirectToRoute('accessing_overview');
        }

        $form = $this->createForm(AccountRegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $accountRegistrationService->register($form->getData());
                $this->addFlash('success', 'Registration complete. Verify your email address to finish activation.');

                return $this->redirectToRoute('accessing_sign_in');
            } catch (\DomainException $exception) {
                $this->addFlash('danger', $exception->getMessage());
            }
        }

        return $this->render('accessing/account/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

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
            $result = $accountAuthenticationService->attemptPasswordSignIn(
                $form->getData()->emailAddress,
                $form->getData()->plainPassword,
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

    #[Route('/sign-in/second-factor', name: 'accessing_sign_in_second_factor', methods: ['GET', 'POST'])]
    public function secondFactorChallenge(
        Request $request,
        AccountRepositoryInterface $accountRepository,
        AccessingAccountAuthenticationServiceInterface $accountAuthenticationService,
        AccessingSecondFactorServiceInterface $secondFactorService,
    ): Response {
        $pendingAccountId = $accountAuthenticationService->getPendingSecondFactorAccountId($request->getSession());

        if ($pendingAccountId === null) {
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
            if ($secondFactorService->verifyChallenge($account, $form->getData()->code)) {
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

    #[Route('/recover/request', name: 'accessing_recover_request', methods: ['GET', 'POST'])]
    public function requestRecovery(
        Request $request,
        AccessingRecoveryServiceInterface $recoveryService,
    ): Response {
        $form = $this->createForm(RecoveryRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $issuedChallenge = $recoveryService->requestPasswordRecovery($form->getData()->emailAddress, $request);
            $this->addFlash('info', 'If an account exists, a password recovery code has been issued.');

            if ($issuedChallenge !== null) {
                $this->addDemoCodeFlash('Password recovery code', $issuedChallenge->plainCode);
            }

            return $this->redirectToRoute('accessing_recover_reset');
        }

        return $this->render('accessing/account/recover_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/recover/reset', name: 'accessing_recover_reset', methods: ['GET', 'POST'])]
    public function resetRecovery(
        Request $request,
        AccessingRecoveryServiceInterface $recoveryService,
    ): Response {
        $form = $this->createForm(RecoveryResetFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($recoveryService->resetPassword(
                $form->getData()->emailAddress,
                $form->getData()->code,
                $form->getData()->newPassword,
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

    private function addDemoCodeFlash(string $label, string $code): void
    {
        if ($this->getParameter('kernel.environment') === 'prod') {
            return;
        }

        $this->addFlash('secondary', sprintf('%s: %s', $label, $code));
    }
}
