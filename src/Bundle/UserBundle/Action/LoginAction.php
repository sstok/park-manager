<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\UserBundle\Action;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment as Twig;

final class LoginAction
{
    private $twig;
    private $authUtils;
    private $loginRoute;
    private $template;

    public function __construct(Twig $twig, AuthenticationUtils $authUtils, string $loginRoute, string $template)
    {
        $this->twig = $twig;
        $this->authUtils = $authUtils;
        $this->loginRoute = $loginRoute;
        $this->template = $template;
    }

    public function __invoke(Request $request)
    {
        $error = $this->authUtils->getLastAuthenticationError();
        $lastUsername = $this->authUtils->getLastUsername();

        return new Response($this->twig->render($this->template, [
            'route' => $this->loginRoute,
            'last_username' => $lastUsername,
            'error' => $error,
        ]));
    }
}
