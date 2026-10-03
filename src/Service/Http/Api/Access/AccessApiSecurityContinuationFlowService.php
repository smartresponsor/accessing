<?php

declare(strict_types=1);

namespace App\Accessing\Service\Http\Api\Access;

use App\Accessing\DTO\Api\Access\AccessApiErrorDTO;
use App\Accessing\DTO\Api\Access\AccessApiIdentityDTO;
use App\Accessing\DTO\Api\Access\AccessApiSessionDTO;
use App\Accessing\Entity\Access\AccessEntity;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessNotificationDeliveryException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\Responder\Api\Access\AccessApiJsonResponder;
use App\Accessing\ServiceInterface\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobilePendingAuthServiceInterface;
use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use App\Accessing\ValueObject\AccessMobilePendingPurpose;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

/**
 * Owns API security continuation flows after the initial sign-in or registration boundary.
 */
final readonly class AccessApiSecurityContinuationFlowService
{
    public function __construct(
        private AccessAuthenticationServiceInterface $authenticationService,
        private AccessApiJsonResponder $responder,
        private Security $security,
        private ?AccessRepositoryInterface $accessRepository = null,
        private ?AccessRecoveryServiceInterface $recoveryService = null,
        private ?AccessVerificationChallengeServiceInterface $verificationChallengeService = null,
        private ?AccessSecondFactorServiceInterface $secondFactorService = null,
        private ?AccessMobileTokenServiceInterface $mobileTokenService = null,
        private ?AccessMobilePendingAuthServiceInterface $mobilePendingAuthService = null,
    ) {
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
                new AccessApiErrorDTO('verification_resend_rate_limited', 'Too many verification resend attempts.'),
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        if (null !== $pendingAuth) {
            $this->mobilePendingAuthService->consume($pendingToken, AccessMobilePendingPurpose::EmailVerification);
            $replacement = $this->mobilePendingAuthService->issue($user, AccessMobilePendingPurpose::EmailVerification, $pendingAuth->getDeviceName());

            return $this->responder->session(new AccessApiSessionDTO(
                'verification_pending',
                $this->identityFromUser($user),
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
                new AccessApiErrorDTO('invalid_verification_code', 'The verification code is invalid or expired.'),
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

            return $this->responder->session(new AccessApiSessionDTO(
                'second_factor_pending',
                $this->identityFromUser($pendingAuth->getUser()),
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
                new AccessApiErrorDTO('invalid_second_factor_code', 'The second-factor code is invalid.'),
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

    public function requestRecovery(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $payload = $this->decodeJsonPayload($request, $fieldErrors);
        $email = $this->stringField($payload, 'email', $fieldErrors);
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
            new AccessApiSessionDTO('recovery_requested', null, null, null, null, false, false),
            Response::HTTP_ACCEPTED,
        );
    }

    public function resetRecovery(Request $request): JsonResponse
    {
        $fieldErrors = [];
        $payload = $this->decodeJsonPayload($request, $fieldErrors);
        $email = $this->stringField($payload, 'email', $fieldErrors);
        $code = $this->stringField($payload, 'code', $fieldErrors);
        $password = $this->stringField($payload, 'password', $fieldErrors);
        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }
        if (null === $this->recoveryService) {
            return $this->unavailableResponse('recovery_unavailable', 'Access recovery is temporarily unavailable.');
        }
        try {
            $completed = $this->recoveryService->resetPassword($email, $code, $password);
        } catch (AccessCompromisedPasswordException $exception) {
            return $this->responder->error(new AccessApiErrorDTO('password_compromised', $exception->getMessage()), Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (AccessPasswordSafetyUnavailableException $exception) {
            return $this->responder->error(new AccessApiErrorDTO('password_safety_unavailable', $exception->getMessage()), Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $completed
            ? $this->responder->session(new AccessApiSessionDTO('recovery_completed', null, null, null, null, false, false), Response::HTTP_ACCEPTED)
            : $this->responder->error(new AccessApiErrorDTO('invalid_recovery', 'Access recovery was rejected.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function pendingSecondFactorUser(Request $request): ?AccessEntity
    {
        $userId = $this->authenticationService->getPendingSecondFactorUserId($request->getSession());

        return null === $userId || null === $this->accessRepository ? null : $this->accessRepository->findById($userId);
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
        $result = [];
        foreach ($payload as $key => $value) {
            if (!is_string($key)) {
                $fieldErrors['_body'][] = 'The request body must be a JSON object.';

                return [];
            }
            $result[$key] = $value;
        }

        return $result;
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

    /** @param array<string, list<string>> $fieldErrors */
    private function invalidRequestResponse(array $fieldErrors): JsonResponse
    {
        return $this->responder->error(new AccessApiErrorDTO('invalid_request', 'Access API JSON surface request validation failed.', $fieldErrors), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function unavailableResponse(string $code, string $message): JsonResponse
    {
        return $this->responder->error(new AccessApiErrorDTO($code, $message), Response::HTTP_SERVICE_UNAVAILABLE);
    }

    private function unauthorizedResponse(string $code, string $message): JsonResponse
    {
        return $this->responder->error(new AccessApiErrorDTO($code, $message), Response::HTTP_UNAUTHORIZED);
    }

    private function identityFromUser(AccessEntity $user): AccessApiIdentityDTO
    {
        return new AccessApiIdentityDTO(
            $user->getId(),
            $user->getDisplayName(),
            $user->getEmail(),
            $user->isEmailVerified(),
            $user->isSecondFactorEnabled(),
            Uuid::fromString($user->getObjectUuid())->toRfc4122(),
        );
    }

    private function sessionFromUser(string $status, AccessEntity $user, bool $requiresVerification, bool $requiresSecondFactor): AccessApiSessionDTO
    {
        return new AccessApiSessionDTO(
            $status,
            $this->identityFromUser($user),
            null,
            null,
            null,
            $requiresVerification,
            $requiresSecondFactor,
        );
    }

    private function mobileAuthenticatedResponse(AccessEntity $user, string $deviceName): JsonResponse
    {
        if (null === $this->mobileTokenService) {
            return $this->unavailableResponse('mobile_session_unavailable', 'Mobile session transport is temporarily unavailable.');
        }

        $tokens = $this->mobileTokenService->issue($user, $deviceName);

        return $this->responder->session(new AccessApiSessionDTO(
            'authenticated',
            $this->identityFromUser($user),
            $tokens->accessToken,
            $tokens->refreshToken,
            $tokens->accessExpiresAt->format(DATE_ATOM),
            false,
            false,
        ));
    }
}
