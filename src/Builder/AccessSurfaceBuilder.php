<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Builder;

use App\Accessing\Dto\AccessPasswordChangeDto;
use App\Accessing\Dto\AccessPhoneVerificationRequestDto;
use App\Accessing\Dto\AccessVerificationCodeDto;
use App\Accessing\Entity\AccessAccountEntity;
use App\Accessing\Factory\Surface\AccessHomeSurfaceContractFactory;
use App\Accessing\Form\AccessPasswordChangeType;
use App\Accessing\Form\AccessPhoneVerificationRequestType;
use App\Accessing\Form\AccessVerificationCodeType;
use App\Accessing\RepositoryInterface\AccessSecurityEventRepositoryInterface;
use App\Accessing\ServiceInterface\AccountSession\AccessAccountSessionServiceInterface;
use App\Accessing\ServiceInterface\Credential\AccessCredentialServiceInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Interfacing\ServiceInterface\Presentation\SurfaceRenderableInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessSurfaceBuilder
{
    public function __construct(
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
    ) {
    }

    /**
     * Render home entrypoint for signed-in accounts and redirect guests to sign-in.
     */
    public function home(
        AccessSecurityEventRepositoryInterface $securityEventRepository,
        AccessHomeSurfaceContractFactory $surfaceContractFactory,
    ): Response|SurfaceRenderableInterface {
        if (!$this->currentUser() instanceof AccessAccountEntity) {
            return $this->redirectTo('interfacing_welcome_sign_in');
        }

        $account = $this->requireAccount();

        return $surfaceContractFactory->create(
            $account,
            $securityEventRepository->findRecentEventsForAccount($account, 8),
        );
    }

    /**
     * Verify account email ownership using a challenge code.
     */
    public function verifyEmail(
        Request $request,
        AccessVerificationChallengeServiceInterface $verificationChallengeService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $account = $this->requireAccount();
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();

            if ($verificationChallengeService->verifyEmailChallenge($account, $data->code)) {
                $this->flash($request, 'success', 'Email verification completed.');

                return $this->redirectTo('accessing_home');
            }

            $this->flash($request, 'danger', 'That email verification code is invalid or expired.');
        }

        $issuedChallenge = $verificationChallengeService->requestEmailVerification($account);
        $this->flash($request, 'info', 'A fresh email verification code has been issued.');
        $this->addDemoCodeFlash($request, 'Email verification code', $issuedChallenge->plainCode);

        return $pageResponder->respond($pageViewFactory->verifyEmail(
            $account,
            $form->createView(),
        ));
    }

    /**
     * Request a phone verification code and hand it to the provider.
     */
    public function requestPhone(
        Request $request,
        AccessVerificationChallengeServiceInterface $verificationChallengeService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $account = $this->requireAccount();
        $form = $this->formFactory->create(AccessPhoneVerificationRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessPhoneVerificationRequestDto $data */
            $data = $form->getData();
            $issuedChallenge = $verificationChallengeService->requestPhoneVerification($account, $data->phoneNumber);

            $this->flash($request, 'info', 'Phone verification code sent.');
            $this->addDemoCodeFlash($request, 'Phone verification code', $issuedChallenge->plainCode);

            return $this->redirectTo('accessing_verify_phone_confirm');
        }

        return $pageResponder->respond($pageViewFactory->requestPhoneVerification(
            $account,
            $form->createView(),
        ));
    }

    /**
     * Confirm a phone verification challenge.
     */
    public function confirmPhone(
        Request $request,
        AccessVerificationChallengeServiceInterface $verificationChallengeService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $account = $this->requireAccount();
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();

            if ($verificationChallengeService->verifyPhoneChallenge($account, $data->code)) {
                $this->flash($request, 'success', 'Phone verification completed.');

                return $this->redirectTo('accessing_home');
            }

            $this->flash($request, 'danger', 'That phone verification code is invalid or expired.');
        }

        return $pageResponder->respond($pageViewFactory->confirmPhone(
            $account,
            $form->createView(),
        ));
    }

    /**
     * Complete second-factor enrollment.
     */
    public function secondFactor(
        Request $request,
        AccessSecondFactorServiceInterface $secondFactorService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $account = $this->requireAccount();
        $enrollment = $secondFactorService->beginEnrollment($account);
        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();
            $confirmedEnrollment = $secondFactorService->confirmEnrollment($account, $data->code);

            if (null !== $confirmedEnrollment) {
                $this->flash($request, 'success', 'Second factor is now enabled.');
                $this->flash($request, 'warning', 'Save the recovery codes shown on the page now. They will not be shown again.');

                return $pageResponder->respond($pageViewFactory->secondFactor(
                    $account,
                    $form->createView(),
                    $confirmedEnrollment,
                    true,
                    true,
                ));
            }

            $this->flash($request, 'danger', 'That authenticator code was not accepted.');
        }

        return $pageResponder->respond($pageViewFactory->secondFactor(
            $account,
            $form->createView(),
            $enrollment,
            $account->getSecondFactor()?->isEnabled() ?? false,
            false,
        ));
    }

    /**
     * Disable second-factor enrollment and recovery codes.
     */
    public function disableSecondFactorNotAllowed(): Response
    {
        return new Response('', Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function disableSecondFactor(
        Request $request,
        AccessSecondFactorServiceInterface $secondFactorService,
    ): Response|SurfaceRenderableInterface {
        $secondFactorService->disableSecondFactor($this->requireAccount());
        $this->flash($request, 'info', 'Second factor has been disabled.');

        return $this->redirectTo('accessing_second_factor');
    }

    /**
     * Render session management page.
     */
    public function sessions(
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->sessions($this->requireAccount()));
    }

    /**
     * Invalidate all active sessions except current one.
     */
    public function invalidateOtherSessions(
        Request $request,
        AccessAccountSessionServiceInterface $accountSessionService,
    ): Response|SurfaceRenderableInterface {
        $invalidatedCount = $accountSessionService->invalidateOtherSessions($this->requireAccount(), $request->getSession());
        $this->flash($request, 'info', sprintf('%d other session(s) invalidated.', $invalidatedCount));

        return $this->redirectTo('accessing_sessions');
    }

    /**
     * Render recent security events for current account.
     */
    public function securityEvents(
        AccessSecurityEventRepositoryInterface $securityEventRepository,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        return $pageResponder->respond($pageViewFactory->securityEvents(
            $securityEventRepository->findRecentEventsForAccount($this->requireAccount()),
        ));
    }

    /**
     * Change account password after current-password verification.
     */
    public function password(
        Request $request,
        AccessCredentialServiceInterface $credentialService,
        AccessPageViewFactoryInterface $pageViewFactory,
        AccessPageResponderInterface $pageResponder,
    ): Response|SurfaceRenderableInterface {
        $account = $this->requireAccount();
        $form = $this->formFactory->create(AccessPasswordChangeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessPasswordChangeDto $data */
            $data = $form->getData();

            if (!$credentialService->verifyPassword($account, $data->currentPassword)) {
                $this->flash($request, 'danger', 'The current password is incorrect.');
            } else {
                $credentialService->changePassword($account, $data->newPassword);
                $this->flash($request, 'success', 'Password updated.');

                return $this->redirectTo('accessing_home');
            }
        }

        return $pageResponder->respond($pageViewFactory->password($account, $form->createView()));
    }

    private function requireAccount(): AccessAccountEntity
    {
        $account = $this->currentUser();

        if (!$account instanceof AccessAccountEntity) {
            throw new AccessDeniedHttpException('Account access required.');
        }

        return $account;
    }

    private function currentUser(): ?AccessAccountEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessAccountEntity ? $user : null;
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
}
