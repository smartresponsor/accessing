<?php

declare(strict_types=1);

namespace App\Accessing\Factory\Surface;

use App\Accessing\Dto\PageView;
use App\Accessing\Value\Surface\AccessingPageSurfaceContract;

final readonly class AccessingPageSurfaceContractFactory
{
    /**
     * @var array<string, string>
     */
    private const TEMPLATE_MAP = [
        'account.overview' => 'access/overview.html.twig',
        'account.verify_email' => 'access/verify_email.html.twig',
        'account.verify_phone_request' => 'access/verify_phone_request.html.twig',
        'account.verify_phone_confirm' => 'access/verify_phone_confirm.html.twig',
        'account.second_factor' => 'access/second_factor.html.twig',
        'account.sessions' => 'access/sessions.html.twig',
        'account.password' => 'access/password.html.twig',
        'account.operator_index' => 'access/operator_index.html.twig',
        'account.operator_detail' => 'access/operator_detail.html.twig',
        'account.register' => 'access/sign_up.html.twig',
        'account.sign_in' => 'accessin/signin/index.html.twig',
        'account.second_factor_challenge' => 'access/account/second_factor_challenge.html.twig',
        'account.recover_request' => 'access/recover.html.twig',
        'account.recover_reset' => 'access/recover_reset.html.twig',
        'security_event.index' => 'access/security_event/index.html.twig',
        'security_event.operator_index' => 'access/security_event/operator_index.html.twig',
        'reset_password.request' => 'access/request.html.twig',
        'reset_password.check_email' => 'access/check_email.html.twig',
        'reset_password.reset' => 'access/reset.html.twig',
    ];

    public function create(PageView $pageView): AccessingPageSurfaceContract
    {
        return new AccessingPageSurfaceContract(
            view: $pageView->view,
            templateName: self::TEMPLATE_MAP[$pageView->view] ?? throw new \LogicException(sprintf('No template mapping configured for page view "%s".', $pageView->view)),
            parameters: $pageView->parameters,
            statusCode: $pageView->statusCode,
        );
    }
}
