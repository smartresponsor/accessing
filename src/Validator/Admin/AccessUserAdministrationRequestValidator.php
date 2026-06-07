<?php

declare(strict_types=1);

namespace App\Accessing\Validator\Admin;

use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationActionCatalogInterface;
use App\Accessing\ServiceInterface\Admin\AccessUserAdministrationRequestValidatorInterface;
use App\Accessing\Value\Admin\AccessUserAdministrationRequest;
use App\Accessing\Value\Admin\AccessUserAdministrationResult;

/**
 * Validates Administering-created controlled user action requests before
 * they reach Accessing-owned user/session/security internals.
 */
final class AccessUserAdministrationRequestValidator implements AccessUserAdministrationRequestValidatorInterface
{
    public function __construct(private readonly AccessUserAdministrationActionCatalogInterface $actionCatalog)
    {
    }

    public function validate(AccessUserAdministrationRequest $request): AccessUserAdministrationResult
    {
        $supportedActions = array_map(
            static fn ($descriptor): string => $descriptor->key(),
            $this->actionCatalog->descriptors(),
        );

        if (!in_array($request->action(), $supportedActions, true)) {
            return AccessUserAdministrationResult::rejected(
                'Accessing user administration request action is not declared in the controlled action catalog.',
                [
                    'action' => $request->action(),
                    'user_reference' => $request->userReference(),
                ],
            );
        }

        if ('' === trim($request->userReference())) {
            return AccessUserAdministrationResult::rejected(
                'Accessing user administration request requires a non-empty user reference.',
                ['action' => $request->action()],
            );
        }

        if ('' === trim($request->requestedBySubject())) {
            return AccessUserAdministrationResult::rejected(
                'Accessing user administration request requires a non-empty requesting subject.',
                [
                    'action' => $request->action(),
                    'user_reference' => $request->userReference(),
                ],
            );
        }

        if ('' === trim($request->safeReason())) {
            return AccessUserAdministrationResult::rejected(
                'Accessing user administration request requires a safe non-secret reason.',
                [
                    'action' => $request->action(),
                    'user_reference' => $request->userReference(),
                ],
            );
        }

        return AccessUserAdministrationResult::success(
            'Accessing user administration request passed safe validation.',
            [
                'action' => $request->action(),
                'user_reference' => $request->userReference(),
                'requested_by_subject' => $request->requestedBySubject(),
            ],
        );
    }
}
