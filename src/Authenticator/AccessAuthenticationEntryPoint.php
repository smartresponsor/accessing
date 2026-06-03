<?php

# Copyright (c) 2025 Oleksandr Tishchenko / Marketing America Corp
declare(strict_types=1);

namespace App\Accessing\Authenticator;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final readonly class AccessAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    private const SIGN_IN_PATH = '/access/signin';

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        if ($request->hasSession() && $request->isMethodSafe()) {
            $this->saveTargetPath($request->getSession(), 'main', $request->getUri());
        }

        return new RedirectResponse(self::SIGN_IN_PATH);
    }
}
