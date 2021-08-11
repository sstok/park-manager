<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\UI\Web\Form\Type\Webhosting\Space\RegisterWebhostingSpaceForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RegisterWebhostingSpace extends AbstractController
{
    #[Route(path: 'webhosting/space/register', name: 'park_manager.admin.webhosting.space.register', methods: ['POST', 'GET'])]
    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(RegisterWebhostingSpaceForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.webhosting_space.registered'));

            return $this->redirectToRoute('park_manager.admin.webhosting.space.list');
        }

        return $this->renderForm('admin/webhosting/space/register.html.twig', ['form' => $form]);
    }
}
