<?php

declare(strict_types=1);

namespace App\Accessing\Validator\Admin;

use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessAccountAdministrationRequestValidatorInterface;
use App\Accessing\Value\Admin\AccessAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessAccountAdministrationResult;

/**
 * Validates Administering-created controlled account action requests before
 * they reach Accessing-owned account/session/security internals.
 */
final class AccessAccountAdministrationRequestValidator implements AccessAccountAdministrationRequestValidatorInterface
{
    public function __construct(private readonly AccessAccountAdministrationActionCatalogInterface $actionCatalog)
    {
    }

    public function validate(AccessAccountAdministrationRequest $request): AccessAccountAdministrationResult
    {
        $supportedActions = array_map(
            static fn ($descriptor): string => $descriptor->key(),
            $this->actionCatalog->descriptors(),
        );

        if (!in_array($request->action(), $supportedActions, true)) {
            return AccessAccountAdministrationResult::rejected(
                'Accessing account administration request action is not declared in the controlled action catalog.',
                [
                    'action' => $request->action(),
                    'account_reference' => $request->accountReference(),
                ],
            );
        }

        if ('' === trim($request->accountReference())) {
            return AccessAccountAdministrationResult::rejected(
                'Accessing account administration request requires a non-empty account reference.',
                ['action' => $request->action()],
            );
        }

        if ('' === trim($request->requestedBySubject())) {
            return AccessAccountAdministrationResult::rejected(
                'Accessing account administration request requires a non-empty requesting subject.',
                [
                    'action' => $request->action(),
                    'account_reference' => $request->accountReference(),
                ],
            );
        }

        if ('' === trim($request->safeReason())) {
            return AccessAccountAdministrationResult::rejected(
                'Accessing account administration request requires a safe non-secret reason.',
                [
                    'action' => $request->action(),
                    'account_reference' => $request->accountReference(),
                ],
            );
        }

        return AccessAccountAdministrationResult::success(
            'Accessing account administration request passed safe validation.',
            [
                'action' => $request->action(),
                'account_reference' => $request->accountReference(),
                'requested_by_subject' => $request->requestedBySubject(),
            ],
        );
    }
}
