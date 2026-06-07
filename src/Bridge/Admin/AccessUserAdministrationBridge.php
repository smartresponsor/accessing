<?php

declare(strict_types=1);

namespace App\Accessing\Bridge\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationAuditRecorderInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationBridgeInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationRequestValidatorInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationServiceInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationAction;
use App\Accessing\Value\Admin\AccessUserAdministrationAuditEvent;
use App\Accessing\Value\Admin\AccessUserAdministrationRequest;
use App\Accessing\Value\Admin\AccessUserAdministrationResult;

/**
 * Converts controlled user actions into safe result DTOs for Administering.
 */
final class AccessUserAdministrationBridge implements AccessUserAdministrationBridgeInterface
{
    public function __construct(
        private readonly AccessUserAdministrationServiceInterface $userAdministrationService,
        private readonly AccessUserAdministrationAuditRecorderInterface $auditRecorder,
        private readonly AccessUserAdministrationRequestValidatorInterface $requestValidator,
    ) {
    }

    public function executeRequest(AccessUserAdministrationRequest $request): AccessUserAdministrationResult
    {
        $validation = $this->requestValidator->validate($request);

        if (!$validation->succeeded()) {
            $this->auditRecorder->record(AccessUserAdministrationAuditEvent::fromRequestAndResult($request, $validation));

            return $validation;
        }

        $result = $this->execute($request->toAction());
        $this->auditRecorder->record(AccessUserAdministrationAuditEvent::fromRequestAndResult($request, $result));

        return $result;
    }

    public function execute(AccessUserAdministrationAction $action): AccessUserAdministrationResult
    {
        if (!$this->userAdministrationService->supports($action->action())) {
            return AccessUserAdministrationResult::rejected(
                'Accessing user administration action is not supported.',
                [
                    'action' => $action->action(),
                    'user_reference' => $action->userReference(),
                ],
            );
        }

        $this->userAdministrationService->execute($action);

        return AccessUserAdministrationResult::success(
            'Accessing user administration action accepted.',
            [
                'action' => $action->action(),
                'user_reference' => $action->userReference(),
            ],
        );
    }
}
