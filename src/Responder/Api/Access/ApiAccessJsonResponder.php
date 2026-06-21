<?php

declare(strict_types=1);

namespace App\Accessing\Responder\Api\Access;

use App\Accessing\Dto\Api\Access\ApiAccessErrorPayload;
use App\Accessing\Dto\Api\Access\ApiAccessSessionPayload;
use Symfony\Component\HttpFoundation\JsonResponse;

final readonly class ApiAccessJsonResponder
{
    public function session(ApiAccessSessionPayload $payload, int $statusCode = JsonResponse::HTTP_OK): JsonResponse
    {
        return new JsonResponse($payload->toArray(), $statusCode);
    }

    public function error(ApiAccessErrorPayload $payload, int $statusCode = JsonResponse::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse($payload->toArray(), $statusCode);
    }
}
