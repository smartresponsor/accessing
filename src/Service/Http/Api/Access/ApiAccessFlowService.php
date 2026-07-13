<?php

declare(strict_types=1);

namespace App\Accessing\Service\Http\Api\Access;

use App\Accessing\Authenticator\AccessBearerAuthenticator;
use App\Accessing\Dto\AccessPasskeyRelyingPartyConfig;
use App\Accessing\Dto\AccessRegistrationRequest;
use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Dto\Api\Access\ApiAccessErrorPayload;
use App\Accessing\Dto\Api\Access\ApiAccessIdentityPayload;
use App\Accessing\Dto\Api\Access\ApiAccessRegisterRequest;
use App\Accessing\Dto\Api\Access\ApiAccessSessionPayload;
use App\Accessing\Dto\Api\Access\ApiAccessSignInRequest;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessNotificationDeliveryException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\Responder\Api\Access\ApiAccessJsonResponder;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Context\AccessCurrentContextProviderInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobilePendingAuthServiceInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class ApiAccessFlowService
{
    public function __construct(
        private AccessAuthenticationServiceInterface $authenticationService,
        private AccessRegistrationServiceInterface $registrationService,
        private AccessCurrentContextProviderInterface $currentContextProvider,
        private ApiAccessJsonResponder $responder,
        private Security $security,
        private ?AccessRepositoryInterface $accessRepository = null,
        private ?AccessRecoveryServiceInterface $recoveryService = null,
        private ?AccessVerificationChallengeServiceInterface $verificationChallengeService = null,
        private ?AccessSecondFactorServiceInterface $secondFactorService = null,
        private ?RateLimiterFactory $accessingSignUpLimiter = null,
        private ?AccessMobileTokenServiceInterface $mobileTokenService = null,
        private ?AccessMobilePendingAuthServiceInterface $mobilePendingAuthService = null,
        private ?AccessPasskeyRegistrationServiceInterface $passkeyRegistrationService = null,
        private ?AccessPasskeyAuthenticationServiceInterface $passkeyAuthenticationService = null,
        private string $accessingPasskeyRelyingPartyId = '',
        private string $accessingPasskeyOrigin = '',
    ) {
    }

    public function signIn(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $input = $this->readSignInRequest($request, $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->responder->error(
                new ApiAccessErrorPayload(
                    'invalid_request',
                    'Access API JSON surface is materialized, but request validation failed.',
                    $fieldErrors,
                ),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $result = $this->authenticationService->attemptPasswordSignIn($input->email, $input->password, $request);

        if ($result->authenticated && $result->user instanceof AccessEntity) {
            if (null === $this->mobileTokenService) {
                return $this->unavailableResponse('mobile_session_unavailable', 'Mobile session transport is temporarily unavailable.');
            }

            $tokens = $this->mobileTokenService->issue($result->user, $this->deviceName($request));

            return $this->responder->session(
                new ApiAccessSessionPayload(
                    'authenticated',
                    new ApiAccessIdentityPayload(
                        $result->user->getId(),
                        $result->user->getDisplayName(),
                        $result->user->getEmail(),
                        $result->user->isEmailVerified(),
                        $result->user->isSecondFactorEnabled(),
                    ),
                    $tokens->accessToken,
                    $tokens->refreshToken,
                    $tokens->accessExpiresAt->format(DATE_ATOM),
                    false,
                    false,
                ),
                Response::HTTP_OK,
            );
        }

        if ($result->requiresSecondFactor && $result->user instanceof AccessEntity) {
            if (null === $this->mobilePendingAuthService) {
                return $this->unavailableResponse('mobile_pending_auth_unavailable', 'Mobile continuation is temporarily unavailable.');
            }

            $pending = $this->mobilePendingAuthService->issue($result->user, AccessMobilePendingPurpose::SecondFactor, $this->deviceName($request));

            return $this->responder->session(
                new ApiAccessSessionPayload(
                    'second_factor_pending',
                    null,
                    null,
                    null,
                    $pending->expiresAt->format(DATE_ATOM),
                    false,
                    true,
                    $pending->token,
                ),
                Response::HTTP_ACCEPTED,
            );
        }

        return $this->responder->error(
            new ApiAccessErrorPayload(
                $this->errorCodeForSignInResult($result),
                $result->message,
            ),
            $this->statusCodeForSignInResult($result),
        );
    }

    public function refresh(Request $request): JsonResponse
    {
        if (null === $this->mobileTokenService) {
            return $this->unavailableResponse('mobile_session_unavailable', 'Mobile session transport is temporarily unavailable.');
        }

        $fieldErrors = [];
        $payload = $this->decodeJsonPayload($request, $fieldErrors);
        $refreshToken = $this->stringField($payload, 'refreshToken', $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        try {
            $tokens = $this->mobileTokenService->rotate($refreshToken);
            $user = $this->mobileTokenService->authenticate($tokens->accessToken);
        } catch (\DomainException) {
            return $this->unauthorizedResponse('invalid_refresh_token', 'The mobile refresh token is invalid, expired, or reused.');
        }

        return $this->responder->session(new ApiAccessSessionPayload(
            'authenticated',
            new ApiAccessIdentityPayload($user->getId(), $user->getDisplayName(), $user->getEmail(), $user->isEmailVerified(), $user->isSecondFactorEnabled()),
            $tokens->accessToken,
            $tokens->refreshToken,
            $tokens->accessExpiresAt->format(DATE_ATOM),
            false,
            false,
        ));
    }

    public function register(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $input = $this->readRegisterRequest($request, $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->responder->error(
                new ApiAccessErrorPayload(
                    'invalid_request',
                    'Access API JSON surface is materialized, but request validation failed.',
                    $fieldErrors,
                ),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (null !== $this->accessingSignUpLimiter) {
            $limiterKey = sprintf('%s|%s', mb_strtolower(trim($input->email)), $request->getClientIp() ?? 'unknown');

            if (!$this->accessingSignUpLimiter->create($limiterKey)->consume()->isAccepted()) {
                return $this->responder->error(
                    new ApiAccessErrorPayload('registration_rate_limited', 'Too many registration attempts.'),
                    Response::HTTP_TOO_MANY_REQUESTS,
                );
            }
        }

        $registrationRequest = new AccessRegistrationRequest();
        $registrationRequest->displayName = $input->displayName;
        $registrationRequest->email = $input->email;
        $registrationRequest->plainPassword = $input->password;

        try {
            $user = $this->registrationService->register($registrationRequest);
        } catch (AccessCompromisedPasswordException $exception) {
            return $this->responder->error(
                new ApiAccessErrorPayload('password_compromised', $exception->getMessage()),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (AccessPasswordSafetyUnavailableException $exception) {
            return $this->responder->error(
                new ApiAccessErrorPayload('password_safety_unavailable', $exception->getMessage()),
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        } catch (AccessNotificationDeliveryException $exception) {
            return $this->responder->error(
                new ApiAccessErrorPayload('notification_delivery_unavailable', $exception->getMessage()),
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        } catch (\DomainException $exception) {
            return $this->responder->error(
                new ApiAccessErrorPayload(
                    'access_register_failed',
                    $exception->getMessage(),
                ),
                Response::HTTP_CONFLICT,
            );
        }

        if (null === $this->mobilePendingAuthService) {
            return $this->unavailableResponse('mobile_pending_auth_unavailable', 'Mobile continuation is temporarily unavailable.');
        }

        $pending = $this->mobilePendingAuthService->issue($user, AccessMobilePendingPurpose::EmailVerification, $this->deviceName($request));

        return $this->responder->session(
            new ApiAccessSessionPayload(
                'verification_pending',
                new ApiAccessIdentityPayload($user->getId(), $user->getDisplayName(), $user->getEmail(), $user->isEmailVerified(), $user->isSecondFactorEnabled()),
                null,
                null,
                $pending->expiresAt->format(DATE_ATOM),
                true,
                false,
                $pending->token,
            ),
            Response::HTTP_CREATED,
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->attributes->get(AccessBearerAuthenticator::REQUEST_ATTRIBUTE);
        if (is_string($accessToken) && '' !== trim($accessToken) && null !== $this->mobileTokenService) {
            $this->mobileTokenService->revoke($accessToken);
        }

        $user = $this->security->getUser();
        $this->authenticationService->signOut($user instanceof AccessEntity ? $user : null, $request);

        return $this->responder->session($this->unauthenticatedSession());
    }

    public function session(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if ($user instanceof AccessEntity) {
            return $this->responder->session($this->sessionFromUser('authenticated', $user, false, false));
        }

        $current = $this->currentContextProvider->current();

        if (null === $current) {
            return $this->responder->session($this->unauthenticatedSession());
        }

        $identity = new ApiAccessIdentityPayload(
            (string) $current->userId(),
            $current->displayName(),
            $current->userIdentifier(),
            $current->emailVerified(),
            $current->secondFactorEnabled(),
        );

        return $this->responder->session(
            new ApiAccessSessionPayload(
                'authenticated',
                $identity,
                null,
                null,
                null,
                false,
                false,
            ),
        );
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $payload = '' === trim($request->getContent()) ? [] : $this->decodeJsonPayload($request, $fieldErrors);
        $pendingToken = $this->optionalStringField($payload, 'pendingToken');
        $pendingAuth = null;
        $user = $this->security->getUser();

        if (null !== $pendingToken) {
            if (null === $this->mobilePendingAuthService) {
                return $this->unavailableResponse('mobile_pending_auth_unavailable', 'Mobile continuation is temporarily unavailable.');
            }

            try {
                $pendingAuth = $this->mobilePendingAuthService->resolve($pendingToken, AccessMobilePendingPurpose::EmailVerification);
                $user = $pendingAuth->getUser();
            } catch (\DomainException) {
                return $this->unauthorizedResponse('invalid_pending_token', 'The mobile continuation token is invalid or expired.');
            }
        }

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('verification_requires_session', 'A signed-in access session or pending token is required.');
        }

        if (null === $this->verificationChallengeService) {
            return $this->unavailableResponse('verification_unavailable', 'Access verification is temporarily unavailable.');
        }

        try {
            $issuedChallenge = $this->verificationChallengeService->resendEmailVerification($user, $request);
        } catch (AccessNotificationDeliveryException $exception) {
            return $this->unavailableResponse('notification_delivery_unavailable', $exception->getMessage());
        }

        if (null === $issuedChallenge) {
            return $this->responder->error(
                new ApiAccessErrorPayload('verification_resend_rate_limited', 'Too many verification resend attempts.'),
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        if (null !== $pendingAuth) {
            $this->mobilePendingAuthService->consume($pendingToken, AccessMobilePendingPurpose::EmailVerification);
            $replacement = $this->mobilePendingAuthService->issue($user, AccessMobilePendingPurpose::EmailVerification, $pendingAuth->getDeviceName());

            return $this->responder->session(new ApiAccessSessionPayload(
                'verification_pending',
                new ApiAccessIdentityPayload($user->getId(), $user->getDisplayName(), $user->getEmail(), $user->isEmailVerified(), $user->isSecondFactorEnabled()),
                null,
                null,
                $replacement->expiresAt->format(DATE_ATOM),
                true,
                false,
                $replacement->token,
            ), Response::HTTP_ACCEPTED);
        }

        return $this->responder->session(
            $this->sessionFromUser('verification_pending', $user, true, false),
            Response::HTTP_ACCEPTED,
        );
    }

    public function confirmVerification(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $payload = $this->decodeJsonPayload($request, $fieldErrors);
        $code = $this->stringField($payload, 'code', $fieldErrors);
        $pendingToken = $this->optionalStringField($payload, 'pendingToken');
        $pendingAuth = null;
        $user = $this->security->getUser();

        if (null !== $pendingToken) {
            if (null === $this->mobilePendingAuthService) {
                return $this->unavailableResponse('mobile_pending_auth_unavailable', 'Mobile continuation is temporarily unavailable.');
            }

            try {
                $pendingAuth = $this->mobilePendingAuthService->resolve($pendingToken, AccessMobilePendingPurpose::EmailVerification);
                $user = $pendingAuth->getUser();
            } catch (\DomainException) {
                return $this->unauthorizedResponse('invalid_pending_token', 'The mobile continuation token is invalid or expired.');
            }
        }

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('verification_requires_session', 'A signed-in access session or pending token is required.');
        }

        if (null === $this->verificationChallengeService) {
            return $this->unavailableResponse('verification_unavailable', 'Access verification is temporarily unavailable.');
        }

        if (!$this->verificationChallengeService->completeEmailVerification($user, $code)) {
            return $this->responder->error(
                new ApiAccessErrorPayload('invalid_verification_code', 'The verification code is invalid or expired.'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (null !== $pendingAuth) {
            $this->mobilePendingAuthService->consume($pendingToken, AccessMobilePendingPurpose::EmailVerification);

            return $this->mobileAuthenticatedResponse($user, $pendingAuth->getDeviceName());
        }

        return $this->responder->session(
            $this->sessionFromUser('authenticated', $user, false, false),
            Response::HTTP_ACCEPTED,
        );
    }

    public function challengeSecondFactor(Request $request): JsonResponse
    {
        $payload = [];
        $fieldErrors = [];
        if ('' !== trim($request->getContent())) {
            $payload = $this->decodeJsonPayload($request, $fieldErrors);
        }
        $pendingToken = $this->optionalStringField($payload, 'pendingToken');

        if (null !== $pendingToken) {
            if (null === $this->mobilePendingAuthService) {
                return $this->unavailableResponse('mobile_pending_auth_unavailable', 'Mobile continuation is temporarily unavailable.');
            }

            try {
                $pendingAuth = $this->mobilePendingAuthService->resolve($pendingToken, AccessMobilePendingPurpose::SecondFactor);
            } catch (\DomainException) {
                return $this->unauthorizedResponse('invalid_pending_token', 'The mobile continuation token is invalid or expired.');
            }

            $user = $pendingAuth->getUser();

            return $this->responder->session(new ApiAccessSessionPayload(
                'second_factor_pending',
                new ApiAccessIdentityPayload($user->getId(), $user->getDisplayName(), $user->getEmail(), $user->isEmailVerified(), $user->isSecondFactorEnabled()),
                null,
                null,
                $pendingAuth->getExpiresAt()->format(DATE_ATOM),
                false,
                true,
                $pendingToken,
            ), Response::HTTP_ACCEPTED);
        }

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        $user = $this->pendingSecondFactorUser($request);

        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('second_factor_requires_pending_session', 'A pending second-factor session or token is required.');
        }

        return $this->responder->session(
            $this->sessionFromUser('second_factor_pending', $user, false, true),
            Response::HTTP_ACCEPTED,
        );
    }

    public function verifySecondFactor(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $payload = $this->decodeJsonPayload($request, $fieldErrors);
        $code = $this->stringField($payload, 'code', $fieldErrors);
        $pendingToken = $this->optionalStringField($payload, 'pendingToken');
        $pendingAuth = null;
        $user = null;

        if (null !== $pendingToken) {
            if (null === $this->mobilePendingAuthService) {
                return $this->unavailableResponse('mobile_pending_auth_unavailable', 'Mobile continuation is temporarily unavailable.');
            }

            try {
                $pendingAuth = $this->mobilePendingAuthService->resolve($pendingToken, AccessMobilePendingPurpose::SecondFactor);
                $user = $pendingAuth->getUser();
            } catch (\DomainException) {
                return $this->unauthorizedResponse('invalid_pending_token', 'The mobile continuation token is invalid or expired.');
            }
        } else {
            $user = $this->pendingSecondFactorUser($request);
        }

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('second_factor_requires_pending_session', 'A pending second-factor session or token is required.');
        }

        if (null === $this->secondFactorService) {
            return $this->unavailableResponse('second_factor_unavailable', 'Second-factor verification is temporarily unavailable.');
        }

        if (!$this->secondFactorService->verifyChallenge($user, $code)) {
            return $this->responder->error(
                new ApiAccessErrorPayload('invalid_second_factor_code', 'The second-factor code is invalid.'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if (null !== $pendingAuth) {
            $this->authenticationService->completeMobileSecondFactor($user, $request);
            $this->mobilePendingAuthService->consume($pendingToken, AccessMobilePendingPurpose::SecondFactor);

            return $this->mobileAuthenticatedResponse($user, $pendingAuth->getDeviceName());
        }

        $this->authenticationService->completePendingSecondFactor($user, $request);

        return $this->responder->session(
            $this->sessionFromUser('authenticated', $user, false, false),
            Response::HTTP_ACCEPTED,
        );
    }

    public function passkeyRegistrationOptions(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser();
        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('passkey_registration_requires_session', 'An authenticated access session is required to register a passkey.');
        }
        if (null === $this->passkeyRegistrationService) {
            return $this->unavailableResponse('passkey_registration_unavailable', 'Passkey registration is temporarily unavailable.');
        }

        return new JsonResponse($this->passkeyRegistrationService->issueOptions($user, $this->passkeyRelyingParty($request))->toArray());
    }

    public function passkeyRegistrationComplete(Request $request): JsonResponse
    {
        $user = $this->authenticatedUser();
        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('passkey_registration_requires_session', 'An authenticated access session is required to register a passkey.');
        }
        if (null === $this->passkeyRegistrationService) {
            return $this->unavailableResponse('passkey_registration_unavailable', 'Passkey registration is temporarily unavailable.');
        }

        $fieldErrors = [];
        $payload = $this->decodeJsonPayload($request, $fieldErrors);
        $name = $this->stringField($payload, 'name', $fieldErrors);
        $credential = $this->arrayField($payload, 'credential', $fieldErrors);
        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        try {
            $registered = $this->passkeyRegistrationService->complete($user, $this->passkeyRelyingParty($request), $credential, $name, $request);
        } catch (\DomainException $exception) {
            return $this->responder->error(new ApiAccessErrorPayload('passkey_registration_failed', $exception->getMessage()), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse([
            'status' => 'passkey_registered',
            'credential' => [
                'id' => $registered->getCredentialId(),
                'name' => $registered->getName(),
                'transports' => $registered->getTransports(),
            ],
        ], Response::HTTP_CREATED);
    }

    public function passkeyAuthenticationOptions(Request $request): JsonResponse
    {
        if (null === $this->passkeyAuthenticationService) {
            return $this->unavailableResponse('passkey_authentication_unavailable', 'Passkey authentication is temporarily unavailable.');
        }

        return new JsonResponse($this->passkeyAuthenticationService->issueOptions($this->passkeyRelyingParty($request))->toArray());
    }

    public function passkeyAuthenticationComplete(Request $request): JsonResponse
    {
        if (null === $this->passkeyAuthenticationService) {
            return $this->unavailableResponse('passkey_authentication_unavailable', 'Passkey authentication is temporarily unavailable.');
        }

        $fieldErrors = [];
        $payload = $this->decodeJsonPayload($request, $fieldErrors);
        $credential = $this->arrayField($payload, 'credential', $fieldErrors);
        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        try {
            $user = $this->passkeyAuthenticationService->complete($this->passkeyRelyingParty($request), $credential, $request);
        } catch (\DomainException $exception) {
            return $this->responder->error(new ApiAccessErrorPayload('passkey_authentication_failed', $exception->getMessage()), Response::HTTP_UNAUTHORIZED);
        }

        return $this->mobileAuthenticatedResponse($user, $this->deviceName($request));
    }

    public function requestRecovery(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $email = $this->readEmailRequest($request, $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        if (null === $this->recoveryService) {
            return $this->unavailableResponse('recovery_unavailable', 'Access recovery is temporarily unavailable.');
        }

        try {
            $this->recoveryService->requestPasswordRecovery($email, $request);
        } catch (AccessNotificationDeliveryException $exception) {
            return $this->unavailableResponse('notification_delivery_unavailable', $exception->getMessage());
        }

        return $this->responder->session(
            new ApiAccessSessionPayload('recovery_requested', null, null, null, null, false, false),
            Response::HTTP_ACCEPTED,
        );
    }

    public function resetRecovery(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $payload = $this->decodeJsonPayload($request, $fieldErrors);

        return $this->completeRecoveryPayload($payload, $fieldErrors);
    }

    /**
     * @param array<string, list<string>> $fieldErrors
     */
    private function readEmailRequest(Request $request, array &$fieldErrors): string
    {
        $payload = $this->decodeJsonPayload($request, $fieldErrors);

        return $this->stringField($payload, 'email', $fieldErrors);
    }

    private function pendingSecondFactorUser(Request $request): ?AccessEntity
    {
        $userId = $this->authenticationService->getPendingSecondFactorUserId($request->getSession());

        if (null === $userId || null === $this->accessRepository) {
            return null;
        }

        return $this->accessRepository->findById($userId);
    }

    /**
     * @param array<string, list<string>> $fieldErrors
     */
    private function readSignInRequest(Request $request, array &$fieldErrors): ApiAccessSignInRequest
    {
        $payload = $this->decodeJsonPayload($request, $fieldErrors);

        return new ApiAccessSignInRequest(
            $this->stringField($payload, 'email', $fieldErrors),
            $this->stringField($payload, 'password', $fieldErrors),
        );
    }

    /**
     * @param array<string, list<string>> $fieldErrors
     */
    private function readRegisterRequest(Request $request, array &$fieldErrors): ApiAccessRegisterRequest
    {
        $payload = $this->decodeJsonPayload($request, $fieldErrors);

        return new ApiAccessRegisterRequest(
            $this->stringField($payload, 'displayName', $fieldErrors),
            $this->stringField($payload, 'email', $fieldErrors),
            $this->stringField($payload, 'password', $fieldErrors),
        );
    }

    /**
     * @param array<string, list<string>> $fieldErrors
     *
     * @return array<string, mixed>
     */
    private function decodeJsonPayload(Request $request, array &$fieldErrors): array
    {
        $content = trim($request->getContent());

        if ('' === $content) {
            $fieldErrors['_body'][] = 'A JSON request body is required.';

            return [];
        }

        try {
            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $fieldErrors['_body'][] = 'The request body must be valid JSON.';

            return [];
        }

        if (!is_array($payload)) {
            $fieldErrors['_body'][] = 'The request body must be a JSON object.';

            return [];
        }

        $objectPayload = [];

        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                $fieldErrors['_body'][] = 'The request body must be a JSON object.';

                return [];
            }

            $objectPayload[$key] = $value;
        }

        return $objectPayload;
    }

    /**
     * @param array<string, mixed>        $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private function stringField(array $payload, string $field, array &$fieldErrors): string
    {
        $value = $payload[$field] ?? null;

        if (!is_string($value) || '' === trim($value)) {
            $fieldErrors[$field][] = sprintf('The "%s" field is required.', $field);

            return '';
        }

        return trim($value);
    }

    /** @param array<string, mixed> $payload */
    private function optionalStringField(array $payload, string $field): ?string
    {
        $value = $payload[$field] ?? null;

        return is_string($value) && '' !== trim($value) ? trim($value) : null;
    }

    /**
     * @param array<string, mixed>        $payload
     * @param array<string, list<string>> $fieldErrors
     */
    private function completeRecoveryPayload(array $payload, array $fieldErrors): JsonResponse
    {
        $email = $this->stringField($payload, 'email', $fieldErrors);
        $code = $this->stringField($payload, 'code', $fieldErrors);
        $password = $this->stringField($payload, 'password', $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        if (null === $this->recoveryService) {
            return $this->responder->error(
                new ApiAccessErrorPayload(
                    'recovery_unavailable',
                    'Access recovery is temporarily unavailable.',
                ),
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        try {
            $completed = $this->recoveryService->resetPassword($email, $code, $password);
        } catch (AccessCompromisedPasswordException $exception) {
            return $this->responder->error(
                new ApiAccessErrorPayload('password_compromised', $exception->getMessage()),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (AccessPasswordSafetyUnavailableException $exception) {
            return $this->responder->error(
                new ApiAccessErrorPayload('password_safety_unavailable', $exception->getMessage()),
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        if ($completed) {
            return $this->responder->session(
                new ApiAccessSessionPayload('recovery_completed', null, null, null, null, false, false),
                Response::HTTP_ACCEPTED,
            );
        }

        return $this->responder->error(
            new ApiAccessErrorPayload('invalid_recovery', 'Access recovery was rejected.'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function unauthenticatedSession(): ApiAccessSessionPayload
    {
        return new ApiAccessSessionPayload(
            'unauthenticated',
            null,
            null,
            null,
            null,
            false,
            false,
        );
    }

    /**
     * @param array<string, list<string>> $fieldErrors
     */
    private function invalidRequestResponse(array $fieldErrors): JsonResponse
    {
        return $this->responder->error(
            new ApiAccessErrorPayload(
                'invalid_request',
                'Access API JSON surface request validation failed.',
                $fieldErrors,
            ),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function unavailableResponse(string $code, string $message): JsonResponse
    {
        return $this->responder->error(
            new ApiAccessErrorPayload($code, $message),
            Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }

    private function unauthorizedResponse(string $code, string $message): JsonResponse
    {
        return $this->responder->error(
            new ApiAccessErrorPayload($code, $message),
            Response::HTTP_UNAUTHORIZED,
        );
    }

    private function sessionFromUser(string $status, AccessEntity $user, bool $requiresVerification, bool $requiresSecondFactor): ApiAccessSessionPayload
    {
        return new ApiAccessSessionPayload(
            $status,
            new ApiAccessIdentityPayload(
                $user->getId(),
                $user->getDisplayName(),
                $user->getEmail(),
                $user->isEmailVerified(),
                $user->isSecondFactorEnabled(),
            ),
            null,
            null,
            null,
            $requiresVerification,
            $requiresSecondFactor,
        );
    }

    private function authenticatedUser(): ?AccessEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessEntity ? $user : null;
    }

    private function passkeyRelyingParty(Request $request): AccessPasskeyRelyingPartyConfig
    {
        $relyingPartyId = '' !== trim($this->accessingPasskeyRelyingPartyId) ? trim($this->accessingPasskeyRelyingPartyId) : $request->getHost();
        $origin = '' !== trim($this->accessingPasskeyOrigin) ? rtrim(trim($this->accessingPasskeyOrigin), '/') : $request->getSchemeAndHttpHost();

        return new AccessPasskeyRelyingPartyConfig(
            $relyingPartyId,
            'SmartResponsor Access',
            $origin,
        );
    }

    /**
     * @param array<string, mixed>        $payload
     * @param array<string, list<string>> $fieldErrors
     *
     * @return array<string, mixed>
     */
    private function arrayField(array $payload, string $field, array &$fieldErrors): array
    {
        $value = $payload[$field] ?? null;
        if (!is_array($value)) {
            $fieldErrors[$field][] = sprintf('The "%s" field must be a JSON object.', $field);

            return [];
        }

        $result = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                $fieldErrors[$field][] = sprintf('The "%s" field must be a JSON object.', $field);

                return [];
            }
            $result[$key] = $item;
        }

        return $result;
    }

    private function mobileAuthenticatedResponse(AccessEntity $user, string $deviceName): JsonResponse
    {
        if (null === $this->mobileTokenService) {
            return $this->unavailableResponse('mobile_session_unavailable', 'Mobile session transport is temporarily unavailable.');
        }

        $tokens = $this->mobileTokenService->issue($user, $deviceName);

        return $this->responder->session(new ApiAccessSessionPayload(
            'authenticated',
            new ApiAccessIdentityPayload($user->getId(), $user->getDisplayName(), $user->getEmail(), $user->isEmailVerified(), $user->isSecondFactorEnabled()),
            $tokens->accessToken,
            $tokens->refreshToken,
            $tokens->accessExpiresAt->format(DATE_ATOM),
            false,
            false,
        ));
    }

    private function deviceName(Request $request): string
    {
        $deviceName = trim((string) $request->headers->get('X-Device-Name', ''));

        if ('' !== $deviceName) {
            return mb_substr($deviceName, 0, 255);
        }

        $userAgent = trim((string) $request->headers->get('User-Agent', 'Mobile device'));

        return '' === $userAgent ? 'Mobile device' : mb_substr($userAgent, 0, 255);
    }

    private function errorCodeForSignInResult(AccessSignInResultDto $result): string
    {
        if (str_contains($result->message, 'Too many sign in attempts')) {
            return 'rate_limited';
        }

        if (str_contains($result->message, 'locked until')) {
            return 'account_locked';
        }

        return 'invalid_credentials';
    }

    private function statusCodeForSignInResult(AccessSignInResultDto $result): int
    {
        if (str_contains($result->message, 'Too many sign in attempts')) {
            return Response::HTTP_TOO_MANY_REQUESTS;
        }

        if (str_contains($result->message, 'locked until')) {
            return Response::HTTP_LOCKED;
        }

        return Response::HTTP_UNAUTHORIZED;
    }
}
