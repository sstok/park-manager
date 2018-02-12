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
use ParkManager\Bundle\UserBundle\Form\Handler\ConfirmPasswordResetFormHandler;
use ParkManager\Component\Security\Token\SplitToken;
use ParkManager\Component\ServiceBus\QueryBus;
use ParkManager\Component\User\Model\Query\GetUserByPasswordResetToken;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
final class ConfirmPasswordResetAction
{
    private $queryBus;
    private $twig;
    private $handlerFactory;
    private $template;

    public function __construct(QueryBus $queryBus, Environment $twig, HandlerFactoryInterface $handlerFactory, string $template)
    {
        $this->queryBus = $queryBus;
        $this->twig = $twig;
        $this->handlerFactory = $handlerFactory;
        $this->template = $template;
    }

    public function __invoke(Request $request, string $token): Response
    {
        try {
            $splitToken = SplitToken::fromString($token);
        } catch (\Exception $e) {
            return $this->newErrorResponse('password_reset.error.invalid_token');
        }

        $user = $this->queryBus->handle(new GetUserByPasswordResetToken($splitToken));
        if (null === $user) {
            return $this->newErrorResponse('password_reset.error.no_token');
        }

        $handler = $this->handlerFactory->create(ConfirmPasswordResetFormHandler::class);
        if (($response = $handler->handle($request, ['token' => $splitToken])) instanceof RedirectResponse) {
            return $response;
        }

        $response = new Response($this->twig->render($this->template, ['user' => $user, 'form' => $handler->getForm()->createView()]));
        $response->setPrivate()->setMaxAge(1);

        return $response;
    }

    private function newErrorResponse($message): Response
    {
        return new Response($this->twig->render($this->template, ['error' => $message]), 404);
    }
}
