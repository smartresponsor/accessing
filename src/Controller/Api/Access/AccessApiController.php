<?php

declare(strict_types=1);

namespace App\Accessing\Controller\Api\Access;

use App\Accessing\Service\Http\Api\Access\AccessApiFlowService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final readonly class AccessApiController
{
    public function __construct(
        private AccessApiFlowService $flow,
    ) {
    }

    #[Route('/api/access/session', name: 'api_access_session', methods: ['GET'])]
    public function session(Request $request): JsonResponse
    {
        return $this->flow->session($request);
    }

    #[Route('/api/access/signin', name: 'api_access_signin', methods: ['POST'])]
    public function signIn(Request $request): JsonResponse
    {
        return $this->flow->signIn($request);
    }
}
