<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\Dto\AccessAccountRegistrationRequest;
use App\Accessing\Dto\AccessAccountSignInRequestDto;
use App\Accessing\Dto\AccessRecoveryRequestDto;
use App\Accessing\Dto\AccessRecoveryResetDto;
use App\Accessing\Dto\AccessVerificationCodeDto;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Form\AccessAccountRegistrationType;
use App\Accessing\Form\AccessAccountSignInType;
use App\Accessing\Form\AccessRecoveryRequestType;
use App\Accessing\Form\AccessRecoveryResetType;
use App\Accessing\Form\AccessVerificationCodeType;
use App\Accessing\RepositoryInterface\AccessAccountRepositoryInterface;
use App\Accessing\ServiceInterface\Account\AccessAccountAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Account\AccessAccountRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Interfacing\ServiceInterface\Presentation\SurfaceRenderableInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessSecuritySurfaceBuilder
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
        AccessAccountRegistrationServiceInterface $accountRegistrationService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectTo('accessing_home');
        }

        if ('GET' === $request->getMethod()) {
            return $this->redirectTo('interfacing_welcome_sign_up', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccessAccountRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessAccountRegistrationRequest $data */
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
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectTo('accessing_home');
        }

        if ('GET' === $request->getMethod()) {
            return $this->redirectTo('interfacing_welcome_sign_in', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccessAccountSignInType::class);

        return $pageResponder->respond($pageViewFactory->signIn($form->createView()));
    }

    public function signInTrailingSlash(): Response
    {
        return $this->redirectTo('interfacing_welcome_sign_in', [], Response::HTTP_PERMANENTLY_REDIRECT);
    }

    public function signInSubmit(
        Request $request,
        AccessAccountAuthenticationServiceInterface $accountAuthenticationService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        if ($this->getUser() instanceof AccessAccountEntity) {
            return $this->redirectTo('accessing_home');
        }

        $form = $this->formFactory->create(AccessAccountSignInType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessAccountSignInRequestDto $data */
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
        AccessAccountRepositoryInterface $accountRepository,
        AccessAccountAuthenticationServiceInterface $accountAuthenticationService,
        AccessSecondFactorServiceInterface $secondFactorService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
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

        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
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
        AccessAccountAuthenticationServiceInterface $accountAuthenticationService,
    ): Response|SurfaceRenderableInterface {
        $accountAuthenticationService->signOut(
            $this->getUser() instanceof AccessAccountEntity ? $this->getUser() : null,
            $request,
        );

        return $this->redirectTo('interfacing_welcome_sign_in');
    }

    public function switchAccount(
        Request $request,
        AccessAccountAuthenticationServiceInterface $accountAuthenticationService,
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
        AccessRecoveryServiceInterface $recoveryService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $form = $this->formFactory->create(AccessRecoveryRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRecoveryRequestDto $data */
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
        AccessRecoveryServiceInterface $recoveryService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $form = $this->formFactory->create(AccessRecoveryResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRecoveryResetDto $data */
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
