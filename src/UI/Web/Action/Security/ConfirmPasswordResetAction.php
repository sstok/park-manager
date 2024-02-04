<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Security;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\UI\Web\Form\Type\Security\ConfirmPasswordResetType;
use Rollerworks\Component\SplitToken\SplitToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ConfirmPasswordResetAction extends AbstractController
{
    #[Route(path: '/password-reset/confirm/{token}', name: 'park_manager.security_confirm_password_reset', requirements: ['token' => '.+'], methods: ['GET', 'POST'])]
    public function __invoke(Request $request, SplitToken $token): Response
    {
        $form = $this->createForm(ConfirmPasswordResetType::class, ['reset_token' => $token]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.password_reset_accepted'));

            return $this->redirectToRoute('park_manager.security_login');
        }

        $response = $this->render('security/password_reset_confirm.html.twig', ['form' => $form]);
        $response->setPrivate();
        $response->setMaxAge(1);

        return $response;
    }
}
