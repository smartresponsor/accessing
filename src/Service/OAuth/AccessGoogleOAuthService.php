<?php

declare(strict_types=1);

namespace App\Accessing\Service\OAuth;

use App\Accessing\Dto\AccessExternalIdentityProfile;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AccessGoogleOAuthService
{
    private const string STATE_SESSION_KEY = 'accessing.google_oauth_state';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private bool $accessingGoogleOAuthEnabled,
        private string $accessingGoogleOAuthClientId,
        private string $accessingGoogleOAuthClientSecret,
        private string $accessingGoogleOAuthRedirectOrigin,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->accessingGoogleOAuthEnabled
            && '' !== trim($this->accessingGoogleOAuthClientId)
            && '' !== trim($this->accessingGoogleOAuthClientSecret);
    }

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

    public function complete(Request $request): AccessExternalIdentityProfile
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

        return new AccessExternalIdentityProfile(
            'google',
            $subject,
            $email,
            true === ($data['email_verified'] ?? $data['verified_email'] ?? false),
            $owner->getName(),
            $owner->getAvatar(),
        );
    }

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

    private function redirectUri(Request $request): string
    {
        $path = $this->urlGenerator->generate('access.google_callback');
        $origin = '' !== trim($this->accessingGoogleOAuthRedirectOrigin)
            ? rtrim(trim($this->accessingGoogleOAuthRedirectOrigin), '/')
            : $request->getSchemeAndHttpHost();

        return $origin.$path;
    }
}
