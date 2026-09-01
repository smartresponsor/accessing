<?php

declare(strict_types=1);

namespace App\Accessing\Responder\Api\Access;

use App\Accessing\Dto\Api\Access\AccessApiErrorPayload;
use App\Accessing\Dto\Api\Access\AccessApiSessionPayload;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class AccessApiJsonResponder
{
    public function session(AccessApiSessionPayload $payload, int $statusCode = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse($payload->toArray(), $statusCode);
    }

    public function error(AccessApiErrorPayload $payload, int $statusCode = JsonResponse::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse($payload->toArray(), $statusCode);
    }
}
