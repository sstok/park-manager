<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use Carbon\CarbonImmutable;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\Webhosting\Space\MarkExpirationOfWebhostingSpaceForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AssignExpirationForWebhostingSpace extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/assign-expiration', name: 'park_manager.admin.webhosting.space.assign_expiration', methods: ['POST', 'GET'])]
    public function __invoke(Request $request, Space $space): Response
    {
        if ($space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($space->primaryDomainLabel);
        }

        $form = $this->createForm(MarkExpirationOfWebhostingSpaceForm::class, $space, [
            'confirmation_title' => new TranslatableMessage('webhosting.space.assign_expiration.heading', ['domain_name' => $space->primaryDomainLabel->toString()]),
            'confirmation_message' => new TranslatableMessage('webhosting.space.assign_expiration.message', ['domain_name' => $space->primaryDomainLabel->toString()]),
            'confirmation_label' => 'label.assign_expiration',
            'cancel_route' => ['name' => 'park_manager.admin.webhosting.space.show', 'arguments' => ['space' => $space->id]],
            'required_value' => $space->primaryDomainLabel->toString(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash(
                'success',
                new TranslatableMessage('flash.webhosting_space.marked_for_expiration', [
                    'has_date' => CarbonImmutable::instance($form->get('expirationDate')->getData())->isCurrentDay() ? 'false' : 'true',
                    'date' => $form->get('expirationDate')->getData(),
                ])
            );

            return $this->redirectToRoute('park_manager.admin.webhosting.space.show', ['space' => $space->id]);
        }

        return $this->renderForm('admin/webhosting/space/assign_expiration.html.twig', ['form' => $form, 'space' => $space]);
    }
}
