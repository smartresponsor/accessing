<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Dto\AccessRecoveryRequestDto;
use App\Accessing\Dto\AccessRecoveryResetDto;
use App\Accessing\Dto\AccessRegistrationRequest;
use App\Accessing\Dto\AccessSignInRequestDto;
use App\Accessing\Dto\AccessVerificationCodeDto;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessNotificationDeliveryException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\Form\Access\AccessRecoveryRequestType;
use App\Accessing\Form\Access\AccessRecoveryResetType;
use App\Accessing\Form\Access\AccessRegistrationType;
use App\Accessing\Form\Access\AccessSignInType;
use App\Accessing\Form\Access\AccessVerificationCodeType;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageResponderInterface;
use App\Accessing\ServiceInterface\Rendering\AccessPageViewFactoryInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Interfacing\Contract\Template\InterfaceTemplateRenderableInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessSecurityFlowService
{
    public function __construct(
        private Security $security,
        private FormFactoryInterface $formFactory,
        private UrlGeneratorInterface $urlGenerator,
        private KernelInterface $kernel,
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

    public function register(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        if ($this->getUser() instanceof AccessEntity) {
            return $this->redirectTo('access.index');
        }

        if ('GET' === $request->getMethod()) {
            return $this->redirectTo('access.register', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccessRegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRegistrationRequest $data */
            $data = $form->getData();

            $limiterKey = sprintf('%s|%s', mb_strtolower(trim($data->email)), $request->getClientIp() ?? 'unknown');

            if (!$this->accessingSignUpLimiter->create($limiterKey)->consume()->isAccepted()) {
                $this->flash($request, 'warning', 'Too many registration attempts. Please wait before trying again.');

                return $this->pageResponder->respond($this->pageViewFactory->register(
                    $form->createView(),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                ));
            }

            try {
                $this->userRegistrationService->register($data);
                $this->flash($request, 'success', 'Your account has been created. Sign in to continue with email verification.');

                return $this->redirectTo('access.signin', [], Response::HTTP_SEE_OTHER);
            } catch (AccessNotificationDeliveryException) {
                $this->flash($request, 'success', 'Your account has been created.');
                $this->flash($request, 'warning', 'The verification email could not be delivered yet. Sign in now to resend it and continue activation.');

                return $this->redirectTo('access.signin', [], Response::HTTP_SEE_OTHER);
            } catch (\DomainException $exception) {
                $flashType = str_starts_with($exception->getMessage(), 'An account already exists for ')
                    ? 'account_exists'
                    : 'danger';
                $this->flash($request, $flashType, $exception->getMessage());
            }
        }

        return $this->pageResponder->respond($this->pageViewFactory->register(
            $form->createView(),
            $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        ));
    }

    public function signIn(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        if ($this->getUser() instanceof AccessEntity) {
            return $this->redirectTo('access.index');
        }

        if ('GET' === $request->getMethod()) {
            return $this->redirectTo('access.signin', [], Response::HTTP_PERMANENTLY_REDIRECT);
        }

        $form = $this->formFactory->create(AccessSignInType::class);

        return $this->pageResponder->respond($this->pageViewFactory->signIn($form->createView()));
    }

    public function signInTrailingSlash(): Response
    {
        return $this->redirectTo('access.signin', [], Response::HTTP_PERMANENTLY_REDIRECT);
    }

    public function signInSubmit(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        if ($this->getUser() instanceof AccessEntity) {
            return $this->redirectTo('access.index');
        }

        $form = $this->formFactory->create(AccessSignInType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessSignInRequestDto $data */
            $data = $form->getData();
            $result = $this->userAuthenticationService->attemptPasswordSignIn(
                $data->emailAddress,
                $data->plainPassword,
                $request,
            );

            if ($result->authenticated) {
                return $this->redirectTo('access.index');
            }

            if ($result->requiresSecondFactor) {
                $this->flash($request, 'info', 'Enter your authenticator or recovery code to finish signing in.');

                return $this->redirectTo('access.second_factor_challenge');
            }

            $this->flash($request, 'danger', $result->message);
        }

        return $this->pageResponder->respond($this->pageViewFactory->signIn($form->createView()));
    }

    public function passkeyAuthenticationOptions(Request $request): JsonResponse
    {
        if ($this->getUser() instanceof AccessEntity) {
            return new JsonResponse(['error' => 'already_authenticated'], Response::HTTP_CONFLICT);
        }

        return new JsonResponse($this->passkeyAuthenticationService->issueOptions($this->passkeyRelyingParty($request))->toArray());
    }

    public function passkeyAuthenticationComplete(Request $request): JsonResponse
    {
        if ($this->getUser() instanceof AccessEntity) {
            return new JsonResponse(['redirect' => $this->urlGenerator->generate('access.index')]);
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

            return new JsonResponse(['redirect' => $this->urlGenerator->generate('access.index')]);
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'passkey_authentication_failed'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function secondFactorChallenge(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $pendingUserId = $this->userAuthenticationService->getPendingSecondFactorUserId($request->getSession());

        if (null === $pendingUserId) {
            return $this->redirectTo('access.signin');
        }

        $user = $this->userRepository->findById($pendingUserId);

        if (!$user instanceof AccessEntity) {
            $this->userAuthenticationService->clearPendingSecondFactor($request->getSession());

            return $this->redirectTo('access.signin');
        }

        $form = $this->formFactory->create(AccessVerificationCodeType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessVerificationCodeDto $data */
            $data = $form->getData();

            if ($this->secondFactorService->verifyChallenge($user, $data->code)) {
                $this->userAuthenticationService->completePendingSecondFactor($user, $request);
                $this->flash($request, 'success', 'Signed in successfully.');

                return $this->redirectTo('access.index');
            }

            $this->flash($request, 'danger', 'The second factor code was not accepted.');
        }

        return $this->pageResponder->respond($this->pageViewFactory->secondFactorChallenge($user, $form->createView()));
    }

    public function signOut(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $this->userAuthenticationService->signOut(
            $this->getUser() instanceof AccessEntity ? $this->getUser() : null,
            $request,
        );

        return $this->redirectTo('access.signin');
    }

    public function switchUser(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $this->userAuthenticationService->signOut(
            $this->getUser() instanceof AccessEntity ? $this->getUser() : null,
            $request,
        );

        $this->flash($request, 'info', 'Signed out. Use another user to continue.');

        return $this->redirectTo('access.signin');
    }

    public function requestRecovery(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $form = $this->formFactory->create(AccessRecoveryRequestType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRecoveryRequestDto $data */
            $data = $form->getData();
            try {
                $issuedChallenge = $this->recoveryService->requestPasswordRecovery($data->emailAddress, $request);
                $this->flash($request, 'info', 'If an user exists, a password recovery code has been issued.');

                if (null !== $issuedChallenge) {
                    $this->addDemoCodeFlash($request, 'Password recovery code', $issuedChallenge->plainCode);
                }
            } catch (AccessNotificationDeliveryException) {
                $this->flash($request, 'warning', 'Password recovery delivery is temporarily unavailable. Please try again later.');
            }

            return $this->redirectTo('access.recover_reset');
        }

        return $this->pageResponder->respond($this->pageViewFactory->requestRecovery($form->createView()));
    }

    public function resetRecovery(Request $request): Response|InterfaceTemplateRenderableInterface
    {
        $form = $this->formFactory->create(AccessRecoveryResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var AccessRecoveryResetDto $data */
            $data = $form->getData();

            try {
                $completed = $this->recoveryService->resetPassword(
                    $data->emailAddress,
                    $data->code,
                    $data->newPassword,
                );
            } catch (AccessCompromisedPasswordException $exception) {
                $this->flash($request, 'danger', $exception->getMessage());

                return $this->pageResponder->respond($this->pageViewFactory->resetRecovery($form->createView()));
            } catch (AccessPasswordSafetyUnavailableException $exception) {
                $this->flash($request, 'warning', $exception->getMessage());

                return $this->pageResponder->respond($this->pageViewFactory->resetRecovery($form->createView()));
            }

            if ($completed) {
                $this->flash($request, 'success', 'Password recovery completed. You can now sign in.');

                return $this->redirectTo('access.signin');
            }

            $this->flash($request, 'danger', 'Password recovery failed. Check the email address and recovery code.');
        }

        return $this->pageResponder->respond($this->pageViewFactory->resetRecovery($form->createView()));
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();

        if (!$session instanceof FlashBagAwareSessionInterface) {
            return;
        }

        $session->getFlashBag()->add($type, $message);
    }

    private function addDemoCodeFlash(Request $request, string $label, string $code): void
    {
        if ('prod' === $this->kernel->getEnvironment()) {
            return;
        }

        $this->flash($request, 'secondary', sprintf('%s: %s', $label, $code));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function redirectTo(string $route, array $parameters = [], int $status = Response::HTTP_FOUND): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate($route, $parameters), $status);
    }

    private function passkeyRelyingParty(Request $request): AccessPasskeyRelyingPartyConfig
    {
        $relyingPartyId = '' !== trim($this->accessingPasskeyRelyingPartyId) ? trim($this->accessingPasskeyRelyingPartyId) : $request->getHost();
        $origin = '' !== trim($this->accessingPasskeyOrigin) ? rtrim(trim($this->accessingPasskeyOrigin), '/') : $request->getSchemeAndHttpHost();

        return new AccessPasskeyRelyingPartyConfig($relyingPartyId, 'SmartResponsor Access', $origin);
    }

    private function getUser(): ?AccessEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessEntity ? $user : null;
    }
}
