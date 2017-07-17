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

use Hostnet\Component\FormHandler\HandlerFactoryInterface;
use ParkManager\Bundle\UserBundle\Form\Handler\RequestPasswordResetFormHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class RequestPasswordResetAction
{
    private $twig;
    private $handlerFactory;
    private $template;

    public function __construct(Environment $twig, HandlerFactoryInterface $handlerFactory, string $template)
    {
        $this->twig = $twig;
        $this->handlerFactory = $handlerFactory;
        $this->template = $template;
    }

    public function __invoke(Request $request): Response
    {
        $handler = $this->handlerFactory->create(RequestPasswordResetFormHandler::class);

        if (($response = $handler->handle($request)) instanceof RedirectResponse) {
            return $response;
        }

        $response = new Response($this->twig->render($this->template, ['form' => $handler->getForm()->createView()]));
        $response->setPrivate();
        $response->setMaxAge(5);

        return $response;
    }
}
