<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use ParkManager\Application\Command\Webhosting\Space\RemoveSpaceSuspension;
use ParkManager\Domain\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class RemoveSuspensionOfWebhostingSpace extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/remove-access-suspension', name: 'park_manager.admin.webhosting.space.remove_access_suspension', methods: ['POST', 'GET'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory, Space $space): RouteRedirectResponse | TwigResponse
    {
        if ($space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($space->primaryDomainLabel);
        }

        $form = $formFactory->create(ConfirmationForm::class, null, [
            'confirmation_title' => new TranslatableMessage('webhosting.space.remove_suspension.title', ['domain_name' => $space->primaryDomainLabel->toString()]),
            'confirmation_message' => new TranslatableMessage('webhosting.space.remove_suspension.message', ['change_url' => $this->generateUrl('park_manager.admin.webhosting.space.suspend_access', ['space' => $space->id])]),
            'confirmation_label' => 'label.remove_suspension',
            'cancel_route' => ['name' => 'park_manager.admin.webhosting.space.show', 'arguments' => ['space' => $space->id]],
            'command_factory' => static fn () => new RemoveSpaceSuspension($space->id),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.space.show', ['space' => $space->id])
                ->withFlash('success', 'flash.webhosting_space.access_suspension_removed')
            ;
        }

        return new TwigResponse('admin/webhosting/space/remove_access_suspension.html.twig', $form);
    }
}
