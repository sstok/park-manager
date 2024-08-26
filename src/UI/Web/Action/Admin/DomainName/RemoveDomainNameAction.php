<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\DomainName;

use Lifthill\Bridge\Web\Form\Type\ConfirmationForm;
use ParkManager\Application\Command\DomainName\RemoveDomainName;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Translation\TranslatableMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RemoveDomainNameAction extends AbstractController
{
    #[Route(path: 'domain-name/{domainName}/remove', name: 'park_manager.admin.domain_name.remove', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, DomainName $domainName): Response
    {
        if ($domainName->space !== null) {
            $this->addFlash('error', new TranslatableMessage('flash.domain_name_space_owned', ['name' => $domainName->namePair]));

            return $this->redirectToRoute('park_manager.admin.list_domain_names');
        }

        $form = $this->createForm(ConfirmationForm::class, null, [
            'confirmation_title' => 'user_management.remove.heading',
            'confirmation_message' => new TranslatableMessage('domain_name.remove.confirm_warning', ['domainName' => $domainName->namePair->toString()]),
            'confirmation_label' => 'label.remove',
            'cancel_route' => 'park_manager.admin.list_domain_names',
            'command_factory' => static fn () => new RemoveDomainName($domainName->id),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.domain_name_removed'));

            return $this->redirectToRoute('park_manager.admin.list_domain_names', ['user' => $domainName->id->toString()]);
        }

        return $this->render('admin/user/remove.html.twig', ['form' => $form, 'domainName' => $domainName]);
    }
}
