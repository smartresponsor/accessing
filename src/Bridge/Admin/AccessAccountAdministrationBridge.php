<?php

declare(strict_types=1);

namespace App\Accessing\Bridge\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationAuditRecorderInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationBridgeInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationRequestValidatorInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationServiceInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationAction;
use App\Accessing\Value\Admin\AccessAccountAdministrationAuditEvent;
use App\Accessing\Value\Admin\AccessAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessAccountAdministrationResult;

/**
 * Converts controlled account actions into safe result DTOs for Administering.
 */
final class AccessAccountAdministrationBridge implements AccessAccountAdministrationBridgeInterface
{
    public function __construct(
        private readonly AccessAccountAdministrationServiceInterface $accountAdministrationService,
        private readonly AccessAccountAdministrationAuditRecorderInterface $auditRecorder,
        private readonly AccessAccountAdministrationRequestValidatorInterface $requestValidator,
    ) {
    }

    public function executeRequest(AccessAccountAdministrationRequest $request): AccessAccountAdministrationResult
    {
        $validation = $this->requestValidator->validate($request);

        if (!$validation->succeeded()) {
            $this->auditRecorder->record(AccessAccountAdministrationAuditEvent::fromRequestAndResult($request, $validation));

            return $validation;
        }

        $result = $this->execute($request->toAction());
        $this->auditRecorder->record(AccessAccountAdministrationAuditEvent::fromRequestAndResult($request, $result));

        return $result;
    }

    public function execute(AccessAccountAdministrationAction $action): AccessAccountAdministrationResult
    {
        if (!$this->accountAdministrationService->supports($action->action())) {
            return AccessAccountAdministrationResult::rejected(
                'Accessing account administration action is not supported.',
                [
                    'action' => $action->action(),
                    'account_reference' => $action->accountReference(),
                ],
            );
        }

        $this->accountAdministrationService->execute($action);

        return AccessAccountAdministrationResult::success(
            'Accessing account administration action accepted.',
            [
                'action' => $action->action(),
                'account_reference' => $action->accountReference(),
            ],
        );
    }
}
