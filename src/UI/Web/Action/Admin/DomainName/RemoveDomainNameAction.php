<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\DomainName;

use ParkManager\Application\Command\DomainName\RemoveDomainName;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\DomainName\Exception\CannotRemoveInUseDomainName;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RemoveDomainNameAction
{
    #[Route(path: 'domain-name/{domainName}/remove', name: 'park_manager.admin.domain_name.remove', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, DomainName $domainName, FormFactoryInterface $formFactory)
    {
        $form = $formFactory->create(ConfirmationForm::class, null, [
            'confirmation_title' => 'user_management.remove.heading',
            'confirmation_message' => new TranslatableMessage('domain_name.remove.confirm_warning', ['domainName' => $domainName->namePair->toString()]),
            'confirmation_label' => 'label.remove',
            'cancel_route' => 'park_manager.admin.list_domain_names',
            'command_factory' => static fn () => new RemoveDomainName($domainName->id),
            'exception_mapping' => [
                CannotRemoveInUseDomainName::class => static function (CannotRemoveInUseDomainName $e, TranslatorInterface $translator, $options) {
                    // XXX This needs to show all the (Hosting Space) Entities current still using this DomainName.
                    return new FormError('Domain name cannot be removed as it is still used by Space {spaceId}.', null, ['{spaceId}' => $e->current->toString()]);
                },
            ],
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.list_domain_names', ['user' => $domainName->id->toString()])
                ->withFlash('success', 'flash.domain_name_removed');
        }

        return new TwigResponse('admin/user/remove.html.twig', ['form' => $form->createView(), 'domainName' => $domainName]);
    }
}
