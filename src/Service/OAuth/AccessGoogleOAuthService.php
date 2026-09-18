<?php

declare(strict_types=1);

namespace App\Accessing\Service\OAuth;

use App\Accessing\DTO\AccessExternalIdentityProfileDTO;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Defines the google o auth service type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessGoogleOAuthService
{
    private const string STATE_SESSION_KEY = 'accessing.google_oauth_state';

    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private bool $accessingGoogleOAuthEnabled,
        private string $accessingGoogleOAuthClientId,
        private string $accessingGoogleOAuthClientSecret,
        private string $accessingGoogleOAuthRedirectOrigin,
    ) {
    }

    /**
     * Executes the is enabled operation within the canonical Accessing component workflow.
     */
    public function isEnabled(): bool
    {
        return $this->accessingGoogleOAuthEnabled
            && '' !== trim($this->accessingGoogleOAuthClientId)
            && '' !== trim($this->accessingGoogleOAuthClientSecret);
    }

    /**
     * Executes the authorization url operation within the canonical Accessing component workflow.
     */
    public function authorizationUrl(Request $request): string
    {
        $provider = $this->provider($request);
        $authorizationUrl = $provider->getAuthorizationUrl([
            'scope' => ['openid', 'email', 'profile'],
            'prompt' => 'select_account',
        ]);
        $request->getSession()->set(self::STATE_SESSION_KEY, $provider->getState());

        return $authorizationUrl;
    }

    /**
     * Executes the complete operation within the canonical Accessing component workflow.
     */
    public function complete(Request $request): AccessExternalIdentityProfileDTO
    {
        $code = trim((string) $request->query->get('code', ''));
        $state = trim((string) $request->query->get('state', ''));
        $expectedState = $request->getSession()->remove(self::STATE_SESSION_KEY);

        if ('' === $code || !is_string($expectedState) || '' === $expectedState || '' === $state || !hash_equals($expectedState, $state)) {
            throw new \DomainException('Google sign-in state validation failed.');
        }

        $provider = $this->provider($request);
        $token = $provider->getAccessToken('authorization_code', ['code' => $code]);
        if (!$token instanceof AccessToken) {
            throw new \DomainException('Google did not return a supported access token.');
        }
        $owner = $provider->getResourceOwner($token);

        if (!$owner instanceof GoogleUser) {
            throw new \DomainException('Google did not return a supported identity profile.');
        }

        $data = $owner->toArray();
        $subjectValue = $owner->getId();
        if (!is_string($subjectValue) && !is_int($subjectValue)) {
            throw new \DomainException('Google identity profile contains an invalid subject.');
        }
        $subject = trim((string) $subjectValue);
        $email = mb_strtolower(trim((string) $owner->getEmail()));
        if ('' === $subject || '' === $email) {
            throw new \DomainException('Google identity profile is missing its subject or email.');
        }

        return new AccessExternalIdentityProfileDTO(
            'google',
            $subject,
            $email,
            true === ($data['email_verified'] ?? $data['verified_email'] ?? false),
            $owner->getName(),
            $owner->getAvatar(),
        );
    }

    /**
     * Executes the provider operation within the canonical Accessing component workflow.
     */
    private function provider(Request $request): Google
    {
        if (!$this->isEnabled()) {
            throw new \DomainException('Google sign-in is not configured for this application.');
        }

        return new Google([
            'clientId' => trim($this->accessingGoogleOAuthClientId),
            'clientSecret' => trim($this->accessingGoogleOAuthClientSecret),
            'redirectUri' => $this->redirectUri($request),
        ]);
    }

    /**
     * Executes the redirect uri operation within the canonical Accessing component workflow.
     */
    private function redirectUri(Request $request): string
    {
        $path = $this->urlGenerator->generate('access.google_callback');
        $origin = '' !== trim($this->accessingGoogleOAuthRedirectOrigin)
            ? rtrim(trim($this->accessingGoogleOAuthRedirectOrigin), '/')
            : $request->getSchemeAndHttpHost();

        return $origin.$path;
    }
}
