<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Service\Rendering;

use App\Accessing\Dto\PageView;
use App\Accessing\ServiceInterface\Rendering\PageResponderInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class TwigPageResponder implements PageResponderInterface
{
    /**
     * @var array<string, string>
     */
    private const array TEMPLATE_MAP = [
        'account.overview' => 'accessing/account/overview.html.twig',
        'account.verify_email' => 'accessing/account/verify_email.html.twig',
        'account.verify_phone_request' => 'accessing/account/verify_phone_request.html.twig',
        'account.verify_phone_confirm' => 'accessing/account/verify_phone_confirm.html.twig',
        'account.second_factor' => 'accessing/account/second_factor.html.twig',
        'account.sessions' => 'accessing/account/sessions.html.twig',
        'account.password' => 'accessing/account/password.html.twig',
        'account.operator_index' => 'accessing/account/operator_index.html.twig',
        'account.operator_detail' => 'accessing/account/operator_detail.html.twig',
        'account.register' => 'accessing/account/register.html.twig',
        'account.sign_in' => 'accessing/account/sign_in.html.twig',
        'account.second_factor_challenge' => 'accessing/account/second_factor_challenge.html.twig',
        'account.recover_request' => 'accessing/account/recover_request.html.twig',
        'account.recover_reset' => 'accessing/account/recover_reset.html.twig',
        'security_event.index' => 'accessing/security_event/index.html.twig',
        'security_event.operator_index' => 'accessing/security_event/operator_index.html.twig',
        'reset_password.request' => 'accessing/reset_password/request.html.twig',
        'reset_password.check_email' => 'accessing/reset_password/check_email.html.twig',
        'reset_password.reset' => 'accessing/reset_password/reset.html.twig',
    ];

    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function respond(PageView $pageView): Response
    {
        $template = self::TEMPLATE_MAP[$pageView->view] ?? null;

        if (null === $template) {
            throw new \LogicException(sprintf('No template mapping configured for page view "%s".', $pageView->view));
        }

        return new Response(
            $this->twig->render($template, $pageView->parameters),
            $pageView->statusCode,
        );
    }
}
