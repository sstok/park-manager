<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\DomainName;

use ParkManager\Application\Command\DomainName\AssignDomainNameToOwner;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\Exception\CannotTransferInUseDomainName;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceBeingRemoved;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Infrastructure\Service\EntityRenderer;
use ParkManager\UI\Web\Form\RawFormError;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RemoveDomainNameAction
{
    #[Route(path: 'webhosting/space/{space}/domain-name/{domainName}/remove', name: 'park_manager.admin.webhosting.space.domain_name.remove', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, Space $space, DomainName $domainName, FormFactoryInterface $formFactory, EntityRenderer $entityRenderer): TwigResponse | RouteRedirectResponse
    {
        if ($domainName->space === null) {
            return RouteRedirectResponse::toRoute('park_manager.admin.list_domain_names')->withFlash(
                type: 'error',
                message: 'flash.domain_name_not_space_owned',
                arguments: ['name' => $domainName->namePair->toString()]
            );
        }

        if ($domainName->space->isMarkedForRemoval()) {
            throw new WebhostingSpaceBeingRemoved($domainName->space->primaryDomainLabel);
        }

        if ($domainName->space !== $space) {
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.space.domain_name.remove', ['space' => $space->id, 'domainName' => $domainName->id]);
        }

        if ($domainName->isPrimary()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.space.list_domain_names', ['space' => $space->id])
                ->withFlash('error', 'flash.domain_name_cannot_remove_primary');
        }

        $form = $formFactory->create(ConfirmationForm::class, null, [
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
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.space.list_domain_names', ['space' => $space->id])
                ->withFlash('success', 'flash.domain_name_removed');
        }

        return new TwigResponse('admin/webhosting/domain_name/remove.html.twig', ['form' => $form->createView(), 'domain' => $domainName, 'space' => $space]);
    }
}
