<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\DomainName;

use Lifthill\Bridge\Web\Form\Type\ConfirmationForm;
use ParkManager\Application\Command\DomainName\AssignDomainNameToOwner;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\Exception\CannotTransferInUseDomainName;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Infrastructure\Service\EntityRenderer;
use ParkManager\UI\Web\Form\RawFormError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RemoveDomainNameAction extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/domain-name/{domainName}/remove', name: 'park_manager.admin.webhosting.space.domain_name.remove', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, Space $space, DomainName $domainName, EntityRenderer $entityRenderer): Response
    {
        if ($domainName->space === null) {
            $this->addFlash('error', new TranslatableMessage('flash.domain_name_not_space_owned', ['name' => $domainName->namePair->toString()]));

            return $this->redirectToRoute('park_manager.admin.list_domain_names');
        }

        if ($domainName->space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($domainName->space->primaryDomainLabel);
        }

        if ($domainName->space !== $space) {
            return $this->redirectToRoute('park_manager.admin.webhosting.space.domain_name.remove', ['space' => $space->id, 'domainName' => $domainName->id]);
        }

        if ($domainName->isPrimary()) {
            $this->addFlash('error', new TranslatableMessage('flash.domain_name_cannot_remove_primary'));

            return $this->redirectToRoute('park_manager.admin.webhosting.space.list_domain_names', ['space' => $space->id]);
        }

        $form = $this->createForm(ConfirmationForm::class, null, [
            'confirmation_title' => new TranslatableMessage('webhosting.domain_name.remove.heading', ['name' => $domainName->toString()]),
            'confirmation_message' => new TranslatableMessage('webhosting.domain_name.remove.confirm_warning', [
                'domain_name' => $domainName->toString(),
                'primary_name' => $space->primaryDomainLabel,
            ]),
            'required_value' => $domainName->toString(),
            'confirmation_label' => 'label.remove',
            'cancel_route' => ['name' => 'park_manager.admin.webhosting.space.list_domain_names', 'arguments' => ['space' => $space->id]],
            'command_factory' => static fn () => new AssignDomainNameToOwner($domainName->id, $space->owner->id),
            'exception_mapping' => [
                CannotTransferInUseDomainName::class => static fn (CannotTransferInUseDomainName $exception, TranslatorInterface $translator): RawFormError => new RawFormError(
                    $translator->trans(
                        'domain_name.cannot_transfer_in_use_by_space',
                        [
                            'entities' => $entityRenderer->listedBySet($exception->entities, ['is_admin' => true]),
                            'domain_name' => $exception->domainName->name,
                            'domain_tld' => $exception->domainName->tld,
                        ],
                        'validators'
                    ),
                    'domain_name.cannot_transfer_in_use_by_space',
                    cause: $exception
                ),
            ],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.domain_name_removed'));

            return $this->redirectToRoute('park_manager.admin.webhosting.space.list_domain_names', ['space' => $space->id]);
        }

        return $this->render('admin/webhosting/domain_name/remove.html.twig', [
            'form' => $form,
            'domain' => $domainName,
            'space' => $space,
        ]);
    }
}
