<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Security;

use ParkManager\UI\Web\Form\Type\Security\RequestPasswordResetType;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class RequestPasswordResetAction
{
    #[Route(path: '/password-reset', name: 'park_manager.security_request_password_reset', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory): RouteRedirectResponse | TwigResponse
    {
        $form = $formFactory->create(RequestPasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.security_login')
                ->withFlash('success', 'flash.password_reset_send')
            ;
        }

        $response = new TwigResponse('security/password_reset.html.twig', $form);
        $response->setPrivate();
        $response->setMaxAge(1);

        return $response;
    }
}
