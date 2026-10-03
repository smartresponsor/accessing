<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\DTO\AccessRecoveryRequestDTO;
use App\Accessing\DTO\AccessRecoveryResetDTO;
use App\Accessing\DTO\AccessRegistrationRequestDTO;
use App\Accessing\DTO\AccessSignInRequestDTO;
use App\Accessing\DTO\AccessVerificationCodeDTO;
use App\Accessing\Entity\Access\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessNotificationDeliveryException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\FactoryInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\Form\AccessRecoveryRequestType;
use App\Accessing\Form\AccessRecoveryResetType;
use App\Accessing\Form\AccessRegistrationType;
use App\Accessing\Form\AccessSignInType;
use App\Accessing\Form\AccessVerificationCodeType;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ResponderInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Defines the security flow service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessSecurityFlowService
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessHttpFlowSupportService $httpFlowSupport,
        private FormFactoryInterface $formFactory,
        private AccessRegistrationServiceInterface $userRegistrationService,
        private AccessAuthenticationServiceInterface $userAuthenticationService,
        private AccessRepositoryInterface $userRepository,
        private AccessSecondFactorServiceInterface $secondFactorService,
        private AccessRecoveryServiceInterface $recoveryService,
        private AccessPasskeyAuthenticationServiceInterface $passkeyAuthenticationService,
        private RateLimiterFactory $accessingSignUpLimiter,
        private AccessPageViewFactoryInterface $pageViewFactory,
        private AccessPageResponderInterface $pageResponder,
        private string $accessingPasskeyRelyingPartyId = '',
        private string $accessingPasskeyOrigin = '',
    ) {
    }

    /**
     * Executes the register operation within the canonical Accessing component workflow.
     */
    public function register(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        if ($this->httpFlowSupport->currentUser() instanceof AccessEntity) {
            return $this->redirectAfterSignIn();
        }

        if ('GET' === $request->getMethod()) {
            return $this->httpFlowSupport->redirectTo('access.register', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccessRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRegistrationRequestDTO $data */
            $data = $form->getData();

            $limiterKey = sprintf('%s|%s', mb_strtolower(trim($data->email)), $request->getClientIp() ?? 'unknown');

            if (!$this->accessingSignUpLimiter->create($limiterKey)->consume()->isAccepted()) {
                $this->httpFlowSupport->flash($request, 'warning', 'Too many registration attempts. Please wait before trying again.');

                return $this->pageResponder->respond($this->pageViewFactory->register(
                    $form->createView(),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                ));
            }

            try {
                $this->userRegistrationService->register($data);
                $this->httpFlowSupport->flash($request, 'success', 'Your account has been created. Sign in to continue with email verification.');

                return $this->httpFlowSupport->redirectTo('access.signin', [], Response::HTTP_SEE_OTHER);
            } catch (AccessNotificationDeliveryException) {
                $this->httpFlowSupport->flash($request, 'success', 'Your account has been created.');
                $this->httpFlowSupport->flash($request, 'warning', 'The verification email could not be delivered yet. Sign in now to resend it and continue activation.');

                return $this->httpFlowSupport->redirectTo('access.signin', [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $exception) {
                $flashType = str_starts_with($exception->getMessage(), 'An account already exists for ')
                    ? 'account_exists'
                    : 'danger';
                $this->httpFlowSupport->flash($request, $flashType, $exception->getMessage());
            }
        }

        return $this->pageResponder->respond($this->pageViewFactory->register(
            $form->createView(),
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        ));
    }

    /**
     * Executes the sign in operation within the canonical Accessing component workflow.
     */
    public function signIn(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        if ($this->httpFlowSupport->currentUser() instanceof AccessEntity) {
            return $this->redirectAfterSignIn();
        }

        if ('GET' === $request->getMethod()) {
            return $this->httpFlowSupport->redirectTo('access.signin', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccessSignInType::class);

        return $this->pageResponder->respond($this->pageViewFactory->signIn($form->createView()));
    }

    /**
     * Executes the sign in trailing slash operation within the canonical Accessing component workflow.
     */
    public function signInTrailingSlash(): Response
    {
        return $this->httpFlowSupport->redirectTo('access.signin', [], Response::HTTP_PERMANENTLY_REDIRECT);
    }

    /**
     * Executes the sign in submit operation within the canonical Accessing component workflow.
     */
    public function signInSubmit(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        if ($this->httpFlowSupport->currentUser() instanceof AccessEntity) {
            return $this->redirectAfterSignIn();
        }

        $form = $this->formFactory->create(AccessSignInType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessSignInRequestDTO $data */
            $data = $form->getData();
            $result = $this->userAuthenticationService->attemptPasswordSignIn(
                $data->emailAddress,
                $data->plainPassword,
                $request,
            );

            if ($result->authenticated) {
                return $this->redirectAfterSignIn();
            }

            if ($result->requiresSecondFactor) {
                $this->httpFlowSupport->flash($request, 'info', 'Enter your authenticator or recovery code to finish signing in.');

                return $this->httpFlowSupport->redirectTo('access.second_factor_challenge');
            }

            $this->httpFlowSupport->flash($request, 'danger', $result->message);

            return $this->httpFlowSupport->redirectTo('access.signin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->pageResponder->respond($this->pageViewFactory->signIn(
            $form->createView(),
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        ));
    }

    /**
     * Executes the passkey authentication options operation within the canonical Accessing component workflow.
     */
    public function passkeyAuthenticationOptions(Request $request): JsonResponse
    {
        if ($this->httpFlowSupport->currentUser() instanceof AccessEntity) {
            return new JsonResponse(['error' => 'already_authenticated'], Response::HTTP_CONFLICT);
        }

        return new JsonResponse($this->passkeyAuthenticationService->issueOptions($this->passkeyRelyingParty($request))->toArray());
    }

    /**
     * Executes the passkey authentication complete operation within the canonical Accessing component workflow.
     */
    public function passkeyAuthenticationComplete(Request $request): JsonResponse
    {
        if ($this->httpFlowSupport->currentUser() instanceof AccessEntity) {
            return new JsonResponse(['redirect' => $this->postSignInUrl()]);
        }

        try {
            $payload = $request->toArray();
            $credentialPayload = $payload['credential'] ?? null;
            if (!is_array($credentialPayload)) {
                throw new \DomainException('Passkey credential payload is required.');
            }

            $credential = [];
            foreach ($credentialPayload as $key => $value) {
                if (!is_string($key)) {
                    throw new \DomainException('Passkey credential payload must be a JSON object.');
                }
                $credential[$key] = $value;
            }

            $user = $this->passkeyAuthenticationService->complete($this->passkeyRelyingParty($request), $credential, $request);
            $this->userAuthenticationService->completePasskeySignIn($user, $request);

            return new JsonResponse(['redirect' => $this->postSignInUrl()]);
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'passkey_authentication_failed'], Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Executes the second factor challenge operation within the canonical Accessing component workflow.
     */
    public function secondFactorChallenge(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $pendingUserId = $this->userAuthenticationService->getPendingSecondFactorUserId($request->getSession());

        if (null === $pendingUserId) {
            return $this->httpFlowSupport->redirectTo('access.signin');
        }

        $user = $this->userRepository->findById($pendingUserId);

        if (!$user instanceof AccessEntity) {
            $this->userAuthenticationService->clearPendingSecondFactor($request->getSession());

            return $this->httpFlowSupport->redirectTo('access.signin');
        }

        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDTO $data */
            $data = $form->getData();

            if ($this->secondFactorService->verifyChallenge($user, $data->code)) {
                $this->userAuthenticationService->completePendingSecondFactor($user, $request);
                $this->httpFlowSupport->flash($request, 'success', 'Signed in successfully.');

                return $this->redirectAfterSignIn();
            }

            $this->httpFlowSupport->flash($request, 'danger', 'The second factor code was not accepted.');
        }

        return $this->pageResponder->respond($this->pageViewFactory->secondFactorChallenge($user, $form->createView()));
    }

    /**
     * Executes the sign out operation within the canonical Accessing component workflow.
     */
    public function signOut(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $this->userAuthenticationService->signOut(
            $this->httpFlowSupport->currentUser() instanceof AccessEntity ? $this->httpFlowSupport->currentUser() : null,
            $request,
        );

        return $this->httpFlowSupport->redirectTo('access.signin');
    }

    /**
     * Executes the switch user operation within the canonical Accessing component workflow.
     */
    public function switchUser(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $this->userAuthenticationService->signOut(
            $this->httpFlowSupport->currentUser() instanceof AccessEntity ? $this->httpFlowSupport->currentUser() : null,
            $request,
        );

        $this->httpFlowSupport->flash($request, 'info', 'Signed out. Use another user to continue.');

        return $this->httpFlowSupport->redirectTo('access.signin');
    }

    /**
     * Executes the request recovery operation within the canonical Accessing component workflow.
     */
    public function requestRecovery(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $form = $this->formFactory->create(AccessRecoveryRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRecoveryRequestDTO $data */
            $data = $form->getData();
            try {
                $issuedChallenge = $this->recoveryService->requestPasswordRecovery($data->emailAddress, $request);
                $this->httpFlowSupport->flash($request, 'info', 'If an user exists, a password recovery code has been issued.');

                if (null !== $issuedChallenge) {
                    $this->httpFlowSupport->addDemoCodeFlash($request, 'Password recovery code', $issuedChallenge->plainCode);
                }
            } catch (AccessNotificationDeliveryException) {
                $this->httpFlowSupport->flash($request, 'warning', 'Password recovery delivery is temporarily unavailable. Please try again later.');
            }

            return $this->httpFlowSupport->redirectTo('access.recover_reset');
        }

        return $this->pageResponder->respond($this->pageViewFactory->requestRecovery($form->createView()));
    }

    /**
     * Executes the reset recovery operation within the canonical Accessing component workflow.
     */
    public function resetRecovery(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $form = $this->formFactory->create(AccessRecoveryResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRecoveryResetDTO $data */
            $data = $form->getData();

            try {
                $completed = $this->recoveryService->resetPassword(
                    $data->emailAddress,
                    $data->code,
                    $data->newPassword,
                );
            } catch (AccessCompromisedPasswordException $exception) {
                $this->httpFlowSupport->flash($request, 'danger', $exception->getMessage());

                return $this->pageResponder->respond($this->pageViewFactory->resetRecovery($form->createView()));
            } catch (AccessPasswordSafetyUnavailableException $exception) {
                $this->httpFlowSupport->flash($request, 'warning', $exception->getMessage());

                return $this->pageResponder->respond($this->pageViewFactory->resetRecovery($form->createView()));
            }

            if ($completed) {
                $this->httpFlowSupport->flash($request, 'success', 'Password recovery completed. You can now sign in.');

                return $this->httpFlowSupport->redirectTo('access.signin');
            }

            $this->httpFlowSupport->flash($request, 'danger', 'Password recovery failed. Check the email address and recovery code.');
        }

        return $this->pageResponder->respond($this->pageViewFactory->resetRecovery($form->createView()));
    }

    /**
     * Executes the redirect after sign in operation within the canonical Accessing component workflow.
     */
    private function redirectAfterSignIn(): RedirectResponse
    {
        return new RedirectResponse($this->postSignInUrl());
    }

    /**
     * Executes the post sign in url operation within the canonical Accessing component workflow.
     */
    private function postSignInUrl(): string
    {
        return '/product/index';
    }

    /**
     * Executes the passkey relying party operation within the canonical Accessing component workflow.
     */
    private function passkeyRelyingParty(Request $request): AccessPasskeyRelyingPartyConfigDTO
    {
        $relyingPartyId = '' !== trim($this->accessingPasskeyRelyingPartyId) ? trim($this->accessingPasskeyRelyingPartyId) : $request->getHost();
        $origin = '' !== trim($this->accessingPasskeyOrigin) ? rtrim(trim($this->accessingPasskeyOrigin), '/') : $request->getSchemeAndHttpHost();

        return new AccessPasskeyRelyingPartyConfigDTO($relyingPartyId, 'SmartResponsor Access', $origin);
    }
}
