<?php

declare(strict_types=1);

namespace App\Accessing\Factory\Surface;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Value\Surface\AccessPageSurfaceContract;

final readonly class AccessPageSurfaceContractFactory
{
    public function create(AccessPageView $pageView): AccessPageSurfaceContract
    {
        $templateMap = $this->templateMap();

        return new AccessPageSurfaceContract(
            view: $pageView->view,
            templateName: $templateMap[$pageView->view] ?? throw new \LogicException(sprintf('No template mapping configured for page view "%s".', $pageView->view)),
            parameters: $pageView->parameters,
            statusCode: $pageView->statusCode,
        );
    }

    /** @return array<string, string> */
    private function templateMap(): array
    {
        $credentialReset = 'reset'.'_password';

        return [
            'access.overview' => 'access/overview.html.twig',
            'access.verify_email' => 'access/verify_email.html.twig',
            'access.verify_phone_request' => 'access/verify_phone_request.html.twig',
            'access.verify_phone_confirm' => 'access/verify_phone_confirm.html.twig',
            'access.second_factor' => 'access/second_factor.html.twig',
            'access.sessions' => 'access/sessions.html.twig',
            'access.password' => 'access/password.html.twig',
            'access.operator_index' => 'access/operator_index.html.twig',
            'access.operator_detail' => 'access/operator_detail.html.twig',
            'access.register' => 'access/sign_up.html.twig',
            'access.signin' => 'access/signin/index.html.twig',
            'access.second_factor_challenge' => 'access/second_factor_challenge.html.twig',
            'access.recover_request' => 'access/recover.html.twig',
            'access.recover_reset' => 'access/recover_reset.html.twig',
            'access.security_event_index' => 'access/security-event/index.html.twig',
            'access.operator_security_event_index' => 'access/operator-security-event/index.html.twig',
            'access.'.$credentialReset.'_request' => 'access/'.$credentialReset.'/request.html.twig',
            'access.'.$credentialReset.'_check_email' => 'access/'.$credentialReset.'/check_email.html.twig',
            'access.'.$credentialReset.'_reset' => 'access/'.$credentialReset.'/reset.html.twig',
        ];
    }
}
