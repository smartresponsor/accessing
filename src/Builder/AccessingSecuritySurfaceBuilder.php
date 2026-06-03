<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\Dto\AccountRegistrationRequest;
use App\Accessing\Dto\AccountSignInRequestDto;
use App\Accessing\Dto\RecoveryRequestDto;
use App\Accessing\Dto\RecoveryResetDto;
use App\Accessing\Dto\VerificationCodeDto;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Form\AccountRegistrationType;
use App\Accessing\Form\AccountSignInType;
use App\Accessing\Form\RecoveryRequestType;
use App\Accessing\Form\RecoveryResetType;
use App\Accessing\Form\VerificationCodeType;
use App\Accessing\RepositoryInterface\AccountRepositoryInterface;
use App\Accessing\ServiceInterface\Account\AccessingAccountAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Account\AccessingAccountRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessingRecoveryServiceInterface;
use App\Accessing\ServiceInterface\Rendering\PageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\PageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessingSecondFactorServiceInterface;
use App\Interfacing\ServiceInterface\Presentation\SurfaceRenderableInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessingSecuritySurfaceBuilder
{
    public function __construct(
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
    ) {
    }

    public function register(
        Request $request,
        AccessingAccountRegistrationServiceInterface $accountRegistrationService,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectTo('accessing_home');
        }

        if ('GET' === $request->getMethod()) {
            return $this->redirectTo('interfacing_welcome_sign_up', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccountRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccountRegistrationRequest $data */
            $data = $form->getData();

            try {
                $accountRegistrationService->register($data);
                $this->flash($request, 'success', 'Registration complete. Verify your email address to finish activation.');

                return $this->redirectTo('interfacing_welcome_sign_in');
            } catch (\DomainException $exception) {
                $this->flash($request, 'danger', $exception->getMessage());
            }
        }

        return $pageResponder->respond($pageViewFactory->register($form->createView()));
    }

    public function signIn(
        Request $request,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectTo('accessing_home');
        }

        if ('GET' === $request->getMethod()) {
            return $this->redirectTo('interfacing_welcome_sign_in', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccountSignInType::class);

        return $pageResponder->respond($pageViewFactory->signIn($form->createView()));
    }

    public function signInTrailingSlash(): Response
    {
        return $this->redirectTo('interfacing_welcome_sign_in', [], Response::HTTP_PERMANENTLY_REDIRECT);
    }

    public function signInSubmit(
        Request $request,
        AccessingAccountAuthenticationServiceInterface $accountAuthenticationService,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectTo('accessing_home');
        }

        $form = $this->formFactory->create(AccountSignInType::class);
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
                return $this->redirectTo('accessing_home');
            }

            if ($result->requiresSecondFactor) {
                $this->flash($request, 'info', 'Enter your authenticator or recovery code to finish signing in.');

                return $this->redirectTo('accessing_sign_in_second_factor');
            }

            $this->flash($request, 'danger', $result->message);
        }

        return $pageResponder->respond($pageViewFactory->signIn($form->createView()));
    }

    public function secondFactorChallenge(
        Request $request,
        AccountRepositoryInterface $accountRepository,
        AccessingAccountAuthenticationServiceInterface $accountAuthenticationService,
        AccessingSecondFactorServiceInterface $secondFactorService,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $pendingAccountId = $accountAuthenticationService->getPendingSecondFactorAccountId($request->getSession());

        if (null === $pendingAccountId) {
            return $this->redirectTo('interfacing_welcome_sign_in');
        }

        $account = $accountRepository->findById($pendingAccountId);

        if (!$account instanceof AccessAccountEntity) {
            $accountAuthenticationService->clearPendingSecondFactor($request->getSession());

            return $this->redirectTo('interfacing_welcome_sign_in');
        }

        $form = $this->formFactory->create(VerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var VerificationCodeDto $data */
            $data = $form->getData();

            if ($secondFactorService->verifyChallenge($account, $data->code)) {
                $accountAuthenticationService->completePendingSecondFactor($account, $request);
                $this->flash($request, 'success', 'Signed in successfully.');

                return $this->redirectTo('accessing_home');
            }

            $this->flash($request, 'danger', 'The second factor code was not accepted.');
        }

        return $pageResponder->respond($pageViewFactory->secondFactorChallenge($account, $form->createView()));
    }

    public function signOut(
        Request $request,
        AccessingAccountAuthenticationServiceInterface $accountAuthenticationService,
    ): Response|SurfaceRenderableInterface {
        $accountAuthenticationService->signOut(
            $this->getUser() instanceof AccessAccountEntity ? $this->getUser() : null,
            $request,
        );

        return $this->redirectTo('interfacing_welcome_sign_in');
    }

    public function switchAccount(
        Request $request,
        AccessingAccountAuthenticationServiceInterface $accountAuthenticationService,
    ): Response|SurfaceRenderableInterface {
        $accountAuthenticationService->signOut(
            $this->getUser() instanceof AccessAccountEntity ? $this->getUser() : null,
            $request,
        );

        $this->flash($request, 'info', 'Signed out. Use another account to continue.');

        return $this->redirectTo('interfacing_welcome_sign_in');
    }

    public function requestRecovery(
        Request $request,
        AccessingRecoveryServiceInterface $recoveryService,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $form = $this->formFactory->create(RecoveryRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RecoveryRequestDto $data */
            $data = $form->getData();
            $issuedChallenge = $recoveryService->requestPasswordRecovery($data->emailAddress, $request);
            $this->flash($request, 'info', 'If an account exists, a password recovery code has been issued.');

            if (null !== $issuedChallenge) {
                $this->addDemoCodeFlash($request, 'Password recovery code', $issuedChallenge->plainCode);
            }

            return $this->redirectTo('accessing_recover_reset');
        }

        return $pageResponder->respond($pageViewFactory->requestRecovery($form->createView()));
    }

    public function resetRecovery(
        Request $request,
        AccessingRecoveryServiceInterface $recoveryService,
        PageViewFactoryInterface $pageViewFactory,
        PageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $form = $this->formFactory->create(RecoveryResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var RecoveryResetDto $data */
            $data = $form->getData();

            if ($recoveryService->resetPassword(
                $data->emailAddress,
                $data->code,
                $data->newPassword,
            )) {
                $this->flash($request, 'success', 'Password recovery completed. You can now sign in.');

                return $this->redirectTo('interfacing_welcome_sign_in');
            }

            $this->flash($request, 'danger', 'Password recovery failed. Check the email address and recovery code.');
        }

        return $pageResponder->respond($pageViewFactory->resetRecovery($form->createView()));
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $request->getSession()->getFlashBag()->add($type, $message);
    }

    private function addDemoCodeFlash(Request $request, string $label, string $code): void
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        $this->flash($request, 'secondary', sprintf('%s: %s', $label, $code));
    }

    private function redirectTo(string $route, array $parameters = [], int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters), $status);
    }

    private function getUser(): ?AccessAccountEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessAccountEntity ? $user : null;
    }
}
