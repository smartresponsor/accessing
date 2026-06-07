<?php

declare(strict_types=1);

namespace App\Accessing\Factory\Surface;

use App\Accessing\Dto\AccessPageView;
use App\Accessing\Value\Surface\AccessPageSurfaceContract;

final readonly class AccessPageSurfaceContractFactory
{
    /**
     * @var array<string, string>
     */
    private const TEMPLATE_MAP = [
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
        'access.sign_in' => 'accessin/signin/index.html.twig',
        'access.second_factor_challenge' => 'access/second_factor_challenge.html.twig',
        'access.recover_request' => 'access/recover.html.twig',
        'access.recover_reset' => 'access/recover_reset.html.twig',
        'security_event.index' => 'access/security_event/index.html.twig',
        'security_event.operator_index' => 'access/security_event/operator_index.html.twig',
        'reset_password.request' => 'access/request.html.twig',
        'reset_password.check_email' => 'access/check_email.html.twig',
        'reset_password.reset' => 'access/reset.html.twig',
    ];

    public function create(AccessPageView $pageView): AccessPageSurfaceContract
    {
        return new AccessPageSurfaceContract(
            view: $pageView->view,
            templateName: self::TEMPLATE_MAP[$pageView->view] ?? throw new \LogicException(sprintf('No template mapping configured for page view "%s".', $pageView->view)),
            parameters: $pageView->parameters,
            statusCode: $pageView->statusCode,
        );
    }
}
