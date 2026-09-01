<?php

declare(strict_types=1);

namespace App\Accessing\Service\Http\Access;

use App\Accessing\Service\OAuth\AccessGoogleOAuthService;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\OAuth\AccessExternalAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\SecurityEvent\AccessSecurityEventServiceInterface;
use App\Accessing\ValueObject\AccessSecurityEventSeverity;
use App\Accessing\ValueObject\AccessSecurityEventType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessGoogleOAuthFlowService
{
    public function __construct(
        private AccessGoogleOAuthService $googleOAuthService,
        private AccessExternalAuthenticationServiceInterface $externalAuthenticationService,
        private AccessAuthenticationServiceInterface $authenticationService,
        private AccessSecurityEventServiceInterface $securityEventService,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function start(Request $request): RedirectResponse
    {
        try {
            return new RedirectResponse($this->googleOAuthService->authorizationUrl($request));
        } catch (\Throwable $exception) {
            $this->flash($request, 'warning', $exception->getMessage());

            return $this->redirectToSignIn();
        }
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->query->has('error')) {
            $this->flash($request, 'warning', 'Google sign-in was cancelled or denied.');

            return $this->redirectToSignIn();
        }

        try {
            $profile = $this->googleOAuthService->complete($request);
            $user = $this->externalAuthenticationService->resolve($profile, $request);
            $this->authenticationService->completeExternalSignIn($user, $request);
            $this->securityEventService->record(
                AccessSecurityEventType::ExternalSignInSucceeded,
                AccessSecurityEventSeverity::Info,
                $user,
                $request,
                ['provider' => 'google'],
            );

            return new RedirectResponse($this->urlGenerator->generate('access.index'), Response::HTTP_SEE_OTHER);
        } catch (\Throwable $exception) {
            $this->securityEventService->record(
                AccessSecurityEventType::ExternalSignInFailed,
                AccessSecurityEventSeverity::Warning,
                null,
                $request,
                ['provider' => 'google', 'reason' => $exception->getMessage()],
            );
            $this->flash($request, 'danger', $exception->getMessage());

            return $this->redirectToSignIn();
        }
    }

    private function redirectToSignIn(): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('access.signin'), Response::HTTP_SEE_OTHER);
    }

    private function flash(Request $request, string $type, string $message): void
    {
        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add($type, $message);
        }
    }
}
