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
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;

final class RemoveDomainNameAction
{
    #[Route(path: 'domain-name/{domainName}/remove', name: 'park_manager.admin.domain_name.remove', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, DomainName $domainName, FormFactoryInterface $formFactory): TwigResponse | RouteRedirectResponse
    {
        if ($domainName->space !== null) {
            return RouteRedirectResponse::toRoute('park_manager.admin.list_domain_names')->withFlash(
                type: 'error',
                message: 'flash.domain_name_space_owned',
                arguments: ['name' => $domainName->namePair->toString()]
            );
        }

        $form = $formFactory->create(ConfirmationForm::class, null, [
            'confirmation_title' => 'user_management.remove.heading',
            'confirmation_message' => new TranslatableMessage('domain_name.remove.confirm_warning', ['domainName' => $domainName->namePair->toString()]),
            'confirmation_label' => 'label.remove',
            'cancel_route' => 'park_manager.admin.list_domain_names',
            'command_factory' => static fn () => new RemoveDomainName($domainName->id),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.list_domain_names', ['user' => $domainName->id->toString()])
                ->withFlash('success', 'flash.domain_name_removed')
            ;
        }

        return new TwigResponse('admin/user/remove.html.twig', ['form' => $form->createView(), 'domainName' => $domainName]);
    }
}
