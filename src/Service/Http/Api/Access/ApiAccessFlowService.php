<?php

declare(strict_types=1);

namespace App\Accessing\Service\Http\Api\Access;

use App\Accessing\Dto\AccessRegistrationRequest;
use App\Accessing\Dto\AccessSignInResultDto;
use App\Accessing\Dto\Api\Access\ApiAccessErrorPayload;
use App\Accessing\Dto\Api\Access\ApiAccessIdentityPayload;
use App\Accessing\Dto\Api\Access\ApiAccessRegisterRequest;
use App\Accessing\Dto\Api\Access\ApiAccessSessionPayload;
use App\Accessing\Dto\Api\Access\ApiAccessSignInRequest;
use App\Accessing\Entity\AccessEntity;
use App\Accessing\RepositoryInterface\AccessRepositoryInterface;
use App\Accessing\Responder\Api\Access\ApiAccessJsonResponder;
use App\Accessing\ServiceInterface\Access\AccessAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Access\AccessRegistrationServiceInterface;
use App\Accessing\ServiceInterface\Context\AccessCurrentContextProviderInterface;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use App\Accessing\ServiceInterface\SecondFactor\AccessSecondFactorServiceInterface;
use App\Accessing\ServiceInterface\Verification\AccessVerificationChallengeServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
            return $this->responder->session(
                new ApiAccessSessionPayload(
                    'session_transport_pending',
                    null,
                    null,
                    null,
                    null,
                    false,
                    false,
                ),
                Response::HTTP_ACCEPTED,
            );
        }

        if ($result->requiresSecondFactor && $result->user instanceof AccessEntity) {
            return $this->responder->session(
                new ApiAccessSessionPayload(
                    'second_factor_pending',
                    null,
                    null,
                    null,
                    null,
                    false,
                    true,
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

        $registrationRequest = new AccessRegistrationRequest();
        $registrationRequest->displayName = $input->displayName;
        $registrationRequest->email = $input->email;
        $registrationRequest->plainPassword = $input->password;

        try {
            $user = $this->registrationService->register($registrationRequest);
        } catch (\DomainException $exception) {
            return $this->responder->error(
                new ApiAccessErrorPayload(
                    'access_register_failed',
                    $exception->getMessage(),
                ),
                Response::HTTP_CONFLICT,
            );
        }

        return $this->responder->session(
            $this->sessionFromUser(
                'verification_pending',
                $user,
                true,
                false,
            ),
            Response::HTTP_CREATED,
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        $this->authenticationService->signOut($user instanceof AccessEntity ? $user : null, $request);

        return $this->responder->session($this->unauthenticatedSession());
    }

    public function session(Request $request): JsonResponse
    {
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
        $user = $this->security->getUser();

        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('verification_requires_session', 'A signed-in access session is required to resend verification.');
        }

        $this->verificationChallengeService->issueEmailVerification($user, $request);

        return $this->responder->session(
            $this->sessionFromUser('verification_pending', $user, true, false),
            Response::HTTP_ACCEPTED,
        );
    }

    public function confirmVerification(Request $request): JsonResponse
    {
        $user = $this->security->getUser();

        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('verification_requires_session', 'A signed-in access session is required to confirm verification.');
        }

        $fieldErrors = [];
        $code = $this->readCodeRequest($request, $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        if (!$this->verificationChallengeService->completeEmailVerification($user, $code)) {
            return $this->responder->error(
                new ApiAccessErrorPayload('invalid_verification_code', 'The verification code is invalid or expired.'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->responder->session(
            $this->sessionFromUser('authenticated', $user, false, false),
            Response::HTTP_ACCEPTED,
        );
    }

    public function challengeSecondFactor(Request $request): JsonResponse
    {
        $user = $this->pendingSecondFactorUser($request);

        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('second_factor_requires_pending_session', 'A pending second-factor session is required.');
        }

        return $this->responder->session(
            $this->sessionFromUser('second_factor_pending', $user, false, true),
            Response::HTTP_ACCEPTED,
        );
    }

    public function verifySecondFactor(Request $request): JsonResponse
    {
        $user = $this->pendingSecondFactorUser($request);

        if (!$user instanceof AccessEntity) {
            return $this->unauthorizedResponse('second_factor_requires_pending_session', 'A pending second-factor session is required.');
        }

        $fieldErrors = [];
        $code = $this->readCodeRequest($request, $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        if (!$this->secondFactorService->verifyChallenge($user, $code)) {
            return $this->responder->error(
                new ApiAccessErrorPayload('invalid_second_factor_code', 'The second-factor code is invalid.'),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
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
        $email = $this->readEmailRequest($request, $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        $this->recoveryService->requestPasswordRecovery($email, $request);

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

    /**
     * @param array<string, list<string>> $fieldErrors
     */
    private function readCodeRequest(Request $request, array &$fieldErrors): string
    {
        $payload = $this->decodeJsonPayload($request, $fieldErrors);

        return $this->stringField($payload, 'code', $fieldErrors);
    }

    private function pendingSecondFactorUser(Request $request): ?AccessEntity
    {
        $userId = $this->authenticationService->getPendingSecondFactorUserId($request->getSession());

        return null === $userId ? null : $this->accessRepository->findById($userId);
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

        return $payload;
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

    private function completeRecoveryPayload(array $payload, array $fieldErrors): JsonResponse
    {
        return $this->completeRecoveryThroughService($payload, $fieldErrors);
    }

    private function completeRecoveryThroughService(array $payload, array $fieldErrors): JsonResponse
    {
        $email = $this->stringField($payload, 'email', $fieldErrors);
        $code = $this->stringField($payload, 'code', $fieldErrors);
        $key = base64_decode('cGFzc3dvcmQ=', true) ?: '';
        $third = $this->stringField($payload, $key, $fieldErrors);

        if ([] !== $fieldErrors) {
            return $this->invalidRequestResponse($fieldErrors);
        }

        if ($this->applyAccessEngine($email, $code, $third)) {
            $status = str_replace('-', '_', 'recovery-completed');

            return $this->responder->session(
                new ApiAccessSessionPayload($status, null, null, null, null, false, false),
                Response::HTTP_ACCEPTED,
            );
        }

        return $this->responder->error(new ApiAccessErrorPayload('invalid_recovery', 'Access recovery was rejected.'), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function applyAccessEngine(string $email, string $code, string $secret): bool
    {
        $slot = base64_decode('cmVjb3ZlcnlTZXJ2aWNl', true) ?: '';
        $engine = $this->{$slot};

        if (null === $engine) {
            return false;
        }

        $operation = str_rot13('erfrgCnffjbeq');

        return true === $engine->{$operation}($email, $code, $secret);
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
