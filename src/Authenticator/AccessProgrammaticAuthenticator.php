<?php

declare(strict_types=1);

namespace App\Accessing\Authenticator;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Defines the programmatic authenticator type and its canonical responsibility within the Accessing component.
 */
final class AccessProgrammaticAuthenticator extends AbstractAuthenticator
{
    /**
     * Executes the supports operation within the canonical Accessing component workflow.
     */
    public function supports(Request $request): ?bool
    {
        return false;
    }

    /**
     * Executes the authenticate operation within the canonical Accessing component workflow.
     */
    public function authenticate(Request $request): Passport
    {
        throw new \LogicException('AccessProgrammaticAuthenticator is only used through Symfony Security::login().');
    }

    /**
     * Executes the on authentication success operation within the canonical Accessing component workflow.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    /**
     * Executes the on authentication failure operation within the canonical Accessing component workflow.
     */
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception,
    ): ?Response {
        return null;
    }
}
