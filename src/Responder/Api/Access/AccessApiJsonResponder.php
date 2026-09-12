<?php

declare(strict_types=1);

namespace App\Accessing\Responder\Api\Access;

use App\Accessing\DTO\Api\Access\AccessApiErrorDTO;
use App\Accessing\DTO\Api\Access\AccessApiSessionDTO;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines the api json responder type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessApiJsonResponder
{
    /**
     * Executes the session operation within the canonical Accessing component workflow.
     */
    public function session(AccessApiSessionDTO $payload, int $statusCode = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse($payload->toArray(), $statusCode);
    }

    /**
     * Executes the error operation within the canonical Accessing component workflow.
     */
    public function error(AccessApiErrorDTO $payload, int $statusCode = JsonResponse::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse($payload->toArray(), $statusCode);
    }
}
