<?php

declare(strict_types=1);

namespace App\Accessing\Service\Http\Api\Access;

use App\Accessing\DTO\AccessPasskeyRelyingPartyConfigDTO;
use App\Accessing\DTO\Api\Access\AccessApiErrorDTO;
use App\Accessing\DTO\Api\Access\AccessApiIdentityDTO;
use App\Accessing\DTO\Api\Access\AccessApiSessionDTO;
use App\Accessing\Entity\Access\AccessEntity;
use App\Accessing\Responder\Api\Access\AccessApiJsonResponder;
use App\Accessing\ServiceInterface\Mobile\AccessMobileTokenServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyAuthenticationServiceInterface;
use App\Accessing\ServiceInterface\Passkey\AccessPasskeyRegistrationServiceInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Uid\Uuid;

#[AsController]
/**
 * Owns Access API passkey registration and authentication flows.
 */
final readonly class AccessApiPasskeyFlowService
{
    public function __construct(
        private AccessApiJsonResponder $responder,
        private Security $security,
        private ?AccessMobileTokenServiceInterface $mobileTokenService = null,
        private ?AccessPasskeyRegistrationServiceInterface $passkeyRegistrationService = null,
        private ?AccessPasskeyAuthenticationServiceInterface $passkeyAuthenticationService = null,
        private string $accessingPasskeyRelyingPartyId = '',
        private string $accessingPasskeyOrigin = '',
    ) {
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
            return $this->responder->error(new AccessApiErrorDTO('passkey_registration_failed', $exception->getMessage()), Response::HTTP_UNPROCESSABLE_ENTITY);
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
            return $this->responder->error(new AccessApiErrorDTO('passkey_authentication_failed', $exception->getMessage()), Response::HTTP_UNAUTHORIZED);
        }

        return $this->mobileAuthenticatedResponse($user, $this->deviceName($request));
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

    private function unauthorizedResponse(string $code, string $message): JsonResponse
    {
        return $this->responder->error(new AccessApiErrorDTO($code, $message), Response::HTTP_UNAUTHORIZED);
    }

    private function authenticatedUser(): ?AccessEntity
    {
        $user = $this->security->getUser();

        return $user instanceof AccessEntity ? $user : null;
    }

    private function passkeyRelyingParty(Request $request): AccessPasskeyRelyingPartyConfigDTO
    {
        $relyingPartyId = '' !== trim($this->accessingPasskeyRelyingPartyId) ? trim($this->accessingPasskeyRelyingPartyId) : $request->getHost();
        $origin = '' !== trim($this->accessingPasskeyOrigin) ? rtrim(trim($this->accessingPasskeyOrigin), '/') : $request->getSchemeAndHttpHost();

        return new AccessPasskeyRelyingPartyConfigDTO($relyingPartyId, 'SmartResponsor Access', $origin);
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

    /** @param array<string, mixed> $payload
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

    private function deviceName(Request $request): string
    {
        $deviceName = trim((string) $request->headers->get('X-Device-Name', ''));
        if ('' !== $deviceName) {
            return mb_substr($deviceName, 0, 255);
        }

        $userAgent = trim((string) $request->headers->get('User-Agent', 'Mobile device'));

        return mb_substr('Mobile · '.('' !== $userAgent ? $userAgent : 'Unknown client'), 0, 255);
    }
}
