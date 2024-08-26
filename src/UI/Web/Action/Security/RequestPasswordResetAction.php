<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Security;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\UI\Web\Form\Type\Security\RequestPasswordResetType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RequestPasswordResetAction extends AbstractController
{
    #[Route(path: '/password-reset', name: 'park_manager.security_request_password_reset', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(RequestPasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.password_reset_send'));

            return $this->redirectToRoute('park_manager.security_login');
        }

        $response = $this->render('security/password_reset.html.twig', ['form' => $form]);
        $response->setPrivate();
        $response->setMaxAge(1);

        return $response;
    }
}
