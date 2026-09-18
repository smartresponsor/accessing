<?php

declare(strict_types=1);

namespace App\Accessing\Controller\Api\Access;

use App\Accessing\Service\Http\Api\Access\AccessApiFlowService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
/**
 * Defines the api controller type and its canonical responsibility within the Accessing component.
 */
final readonly class AccessApiController
{
    /**
     * Initializes the collaborators required by this Accessing runtime responsibility.
     */
    public function __construct(
        private AccessApiFlowService $flow,
    ) {
    }

    #[Route('/api/access/session', name: 'api_access_session', methods: ['GET'])]
    /**
     * Executes the session operation within the canonical Accessing component workflow.
     */
    public function session(Request $request): JsonResponse
    {
        return $this->flow->session($request);
    }

    #[Route('/api/access/signin', name: 'api_access_signin', methods: ['POST'])]
    /**
     * Executes the sign in operation within the canonical Accessing component workflow.
     */
    public function signIn(Request $request): JsonResponse
    {
        return $this->flow->signIn($request);
    }
}
