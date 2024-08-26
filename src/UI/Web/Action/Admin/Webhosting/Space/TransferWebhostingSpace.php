<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\Webhosting\Space\TransferWebhostingSpaceForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TransferWebhostingSpace extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/transfer-to-owner', name: 'park_manager.admin.webhosting.space.transfer_to_owner', methods: ['POST', 'GET'])]
    public function __invoke(Request $request, Space $space): Response
    {
        if ($space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($space->primaryDomainLabel);
        }

        $form = $this->createForm(TransferWebhostingSpaceForm::class, $space);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.webhosting_space.owner_transferred'));

            return $this->redirectToRoute('park_manager.admin.webhosting.space.show', ['space' => $space->id]);
        }

        return $this->render('admin/webhosting/space/owner_transfer.html.twig', ['form' => $form, 'space' => $space]);
    }
}
