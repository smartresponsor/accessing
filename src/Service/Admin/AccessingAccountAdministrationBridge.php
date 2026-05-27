<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationAuditRecorderInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationBridgeInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationRequestValidatorInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationServiceInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAction;
use App\Accessing\Value\Admin\AccessingAccountAdministrationAuditEvent;
use App\Accessing\Value\Admin\AccessingAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessingAccountAdministrationResult;

/**
 * Converts controlled account actions into safe result DTOs for Administering.
 */
final class AccessingAccountAdministrationBridge implements AccessingAccountAdministrationBridgeInterface
{
    public function __construct(
        private readonly AccessingAccountAdministrationServiceInterface $accountAdministrationService,
        private readonly AccessingAccountAdministrationAuditRecorderInterface $auditRecorder,
        private readonly AccessingAccountAdministrationRequestValidatorInterface $requestValidator,
    ) {
    }

    public function executeRequest(AccessingAccountAdministrationRequest $request): AccessingAccountAdministrationResult
    {
        $validation = $this->requestValidator->validate($request);

        if (!$validation->succeeded()) {
            $this->auditRecorder->record(AccessingAccountAdministrationAuditEvent::fromRequestAndResult($request, $validation));

            return $validation;
        }

        $result = $this->execute($request->toAction());
        $this->auditRecorder->record(AccessingAccountAdministrationAuditEvent::fromRequestAndResult($request, $result));

        return $result;
    }

    public function execute(AccessingAccountAdministrationAction $action): AccessingAccountAdministrationResult
    {
        if (!$this->accountAdministrationService->supports($action->action())) {
            return AccessingAccountAdministrationResult::rejected(
                'Accessing account administration action is not supported.',
                [
                    'action' => $action->action(),
                    'account_reference' => $action->accountReference(),
                ],
            );
        }

        $this->accountAdministrationService->execute($action);

        return AccessingAccountAdministrationResult::success(
            'Accessing account administration action accepted.',
            [
                'action' => $action->action(),
                'account_reference' => $action->accountReference(),
            ],
        );
    }
}
