<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use ParkManager\Application\Command\Webhosting\Space\RemoveSpaceExpirationDate;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RemoveExpirationOfWebhostingSpace extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/remove-expiration', name: 'park_manager.admin.webhosting.space.remove_expiration', methods: ['POST', 'GET'])]
    public function __invoke(Request $request, Space $space): Response
    {
        if ($space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($space->primaryDomainLabel);
        }

        if ($space->expirationDate === null) {
            $this->addFlash('success', new TranslatableMessage('flash.webhosting_space.removed_expiration'));

            return $this->redirectToRoute('park_manager.admin.webhosting.space.show', ['space' => $space->id]);
        }

        $form = $this->createForm(ConfirmationForm::class, $space, [
            'confirmation_title' => new TranslatableMessage('webhosting.space.remove_expiration.heading', ['domain_name' => $space->primaryDomainLabel->toString()]),
            'confirmation_message' => 'webhosting.space.remove_expiration.message',
            'confirmation_label' => 'label.remove',
            'cancel_route' => ['name' => 'park_manager.admin.webhosting.space.show', 'arguments' => ['space' => $space->id]],
            'command_factory' => static fn (): object => new RemoveSpaceExpirationDate($space->id),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.webhosting_space.removed_expiration'));

            return $this->redirectToRoute('park_manager.admin.webhosting.space.show', ['space' => $space->id]);
        }

        return $this->renderForm('admin/webhosting/space/remove_expiration.html.twig', ['form' => $form, 'space' => $space]);
    }
}
