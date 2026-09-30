<?php

declare(strict_types=1);

namespace App\Accessing\Service\Http\Api\Access;

use App\Accessing\DTO\Api\Access\AccessApiErrorDTO;
use App\Accessing\DTO\Api\Access\AccessApiSessionDTO;
use App\Accessing\Exception\AccessCompromisedPasswordException;
use App\Accessing\Exception\AccessNotificationDeliveryException;
use App\Accessing\Exception\AccessPasswordSafetyUnavailableException;
use App\Accessing\Responder\Api\Access\AccessApiJsonResponder;
use App\Accessing\ServiceInterface\Recovery\AccessRecoveryServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
/**
 * Owns Access API password-recovery flows.
 */
final readonly class AccessApiRecoveryFlowService
{
    public function __construct(
        private AccessApiJsonResponder $responder,
        private ?AccessRecoveryServiceInterface $recoveryService = null,
    ) {
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
            return $this->responder->error(
                new AccessApiErrorDTO('password_compromised', $exception->getMessage()),
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        } catch (AccessPasswordSafetyUnavailableException $exception) {
            return $this->responder->error(
                new AccessApiErrorDTO('password_safety_unavailable', $exception->getMessage()),
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        if ($completed) {
            return $this->responder->session(
                new AccessApiSessionDTO('recovery_completed', null, null, null, null, false, false),
                Response::HTTP_ACCEPTED,
            );
        }

        return $this->responder->error(
            new AccessApiErrorDTO('invalid_recovery', 'Access recovery was rejected.'),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    /** @param array<string, list<string>> $fieldErrors */
    private function readEmailRequest(Request $request, array &$fieldErrors): string
    {
        $payload = $this->decodeJsonPayload($request, $fieldErrors);

        return $this->stringField($payload, 'email', $fieldErrors);
    }

    /** @param array<string, list<string>> $fieldErrors */
    private function invalidRequestResponse(array $fieldErrors): JsonResponse
    {
        return $this->responder->error(
            new AccessApiErrorDTO('invalid_request', 'Access API JSON surface request validation failed.', $fieldErrors),
            Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    private function unavailableResponse(string $code, string $message): JsonResponse
    {
        return $this->responder->error(new AccessApiErrorDTO($code, $message), Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /** @param array<string, list<string>> $fieldErrors
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

    /** @param array<string, mixed> $payload
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
}
