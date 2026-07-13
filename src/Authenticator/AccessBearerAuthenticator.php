<?php

declare(strict_types=1);

namespace App\Accessing\Authenticator;

use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class AccessBearerAuthenticator extends AbstractAuthenticator
{
    public const REQUEST_ATTRIBUTE = '_accessing_bearer_token';

    public function __construct(
        private readonly AccessMobileTokenServiceInterface $mobileTokenService,
    ) {
    }

    public function supports(Request $request): bool
    {
        return str_starts_with(trim((string) $request->headers->get('Authorization', '')), 'Bearer ');
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $authorization = trim((string) $request->headers->get('Authorization', ''));
        $accessToken = trim(substr($authorization, 7));
        if ('' === $accessToken) {
            throw new CustomUserMessageAuthenticationException('The mobile access token is missing.');
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $accessToken);

        return new SelfValidatingPassport(new UserBadge(
            hash('sha256', $accessToken),
            function () use ($accessToken) {
                try {
                    return $this->mobileTokenService->authenticate($accessToken);
                } catch (\DomainException) {
                    throw new CustomUserMessageAuthenticationException('The mobile access token is invalid or expired.');
                }
            },
        ));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse([
            'error' => [
                'code' => 'invalid_access_token',
                'message' => 'The mobile access token is invalid or expired.',
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
