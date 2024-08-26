<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\DomainName;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\UI\Web\Form\Type\DomainName\EditDomainNameForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EditDomainName extends AbstractController
{
    #[Route(path: 'domain-name/{domainName}/edit/', name: 'park_manager.admin.domain_name.edit', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, DomainName $domainName): Response
    {
        if ($domainName->space !== null) {
            $this->addFlash('error', new TranslatableMessage('flash.domain_name_space_owned', ['name' => $domainName->namePair]));

            return $this->redirectToRoute('park_manager.admin.list_domain_names');
        }

        $form = $this->createForm(EditDomainNameForm::class, $domainName);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.domain_name_changed'));

            return $this->redirectToRoute('park_manager.admin.list_domain_names');
        }

        return $this->render('admin/domain_name/edit.html.twig', ['form' => $form, 'domainName' => $domainName]);
    }
}
