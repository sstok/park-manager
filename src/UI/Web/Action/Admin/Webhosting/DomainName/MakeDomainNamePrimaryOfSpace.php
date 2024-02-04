<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\DomainName;

use Lifthill\Bridge\Web\Form\Type\ConfirmationForm;
use ParkManager\Application\Command\DomainName\AssignDomainNameToSpace;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\Space;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class MakeDomainNamePrimaryOfSpace extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/domain-name/{domainName}/make-primary', name: 'park_manager.admin.webhosting.space.domain_name.make_primary', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, Space $space, DomainName $domainName): Response
    {
        if ($domainName->space === null) {
            $this->addFlash('error', new TranslatableMessage('flash.domain_name_not_space_owned', ['name' => $domainName->namePair->toString()]));

            return $this->redirectToRoute('park_manager.admin.list_domain_names');
        }

        if ($domainName->space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($domainName->space->primaryDomainLabel);
        }

        if ($domainName->space !== $space) {
            return $this->redirectToRoute('park_manager.admin.webhosting.space.domain_name.make_primary', ['space' => $space->id, 'domainName' => $domainName->id]);
        }

        $form = $this->createForm(ConfirmationForm::class, $domainName, [
            'confirmation_title' => new TranslatableMessage('webhosting.domain_name.make_primary.heading', ['domain_name' => $domainName->toString()]),
            'confirmation_message' => new TranslatableMessage('webhosting.domain_name.make_primary.message', ['current_name' => $space->primaryDomainLabel]),
            'confirmation_label' => 'label.make_primary',
            'cancel_route' => ['name' => 'park_manager.admin.webhosting.space.list_domain_names', 'arguments' => ['space' => $space->id]],
            'command_factory' => static fn (): object => new AssignDomainNameToSpace($domainName->id, $space->id, true),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.domain_name_marked_as_primary'));

            return $this->redirectToRoute('park_manager.admin.webhosting.space.list_domain_names', ['space' => $space->id]);
        }

        return $this->render('admin/webhosting/domain_name/make_primary.html.twig', [
            'form' => $form,
            'domainName' => $domainName,
            'space' => $space,
        ]);
    }
}
