<?php

declare(strict_types=1);

namespace App\Accessing\Service\Admin;

use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessingAccountAdministrationRequestValidatorInterface;
use App\Accessing\Value\Admin\AccessingAccountAdministrationRequest;
use App\Accessing\Value\Admin\AccessingAccountAdministrationResult;

/**
 * Validates Administering-created controlled account action requests before
 * they reach Accessing-owned account/session/security internals.
 */
final class AccessingAccountAdministrationRequestValidator implements AccessingAccountAdministrationRequestValidatorInterface
{
    public function __construct(private readonly AccessingAccountAdministrationActionCatalogInterface $actionCatalog)
    {
    }

    public function validate(AccessingAccountAdministrationRequest $request): AccessingAccountAdministrationResult
    {
        $supportedActions = array_map(
            static fn ($descriptor): string => $descriptor->key(),
            $this->actionCatalog->descriptors(),
        );

        if (!in_array($request->action(), $supportedActions, true)) {
            return AccessingAccountAdministrationResult::rejected(
                'Accessing account administration request action is not declared in the controlled action catalog.',
                [
                    'action' => $request->action(),
                    'account_reference' => $request->accountReference(),
                ],
            );
        }

        if ('' === trim($request->accountReference())) {
            return AccessingAccountAdministrationResult::rejected(
                'Accessing account administration request requires a non-empty account reference.',
                ['action' => $request->action()],
            );
        }

        if ('' === trim($request->requestedBySubject())) {
            return AccessingAccountAdministrationResult::rejected(
                'Accessing account administration request requires a non-empty requesting subject.',
                [
                    'action' => $request->action(),
                    'account_reference' => $request->accountReference(),
                ],
            );
        }

        if ('' === trim($request->safeReason())) {
            return AccessingAccountAdministrationResult::rejected(
                'Accessing account administration request requires a safe non-secret reason.',
                [
                    'action' => $request->action(),
                    'account_reference' => $request->accountReference(),
                ],
            );
        }

        return AccessingAccountAdministrationResult::success(
            'Accessing account administration request passed safe validation.',
            [
                'action' => $request->action(),
                'account_reference' => $request->accountReference(),
                'requested_by_subject' => $request->requestedBySubject(),
            ],
        );
    }
}
