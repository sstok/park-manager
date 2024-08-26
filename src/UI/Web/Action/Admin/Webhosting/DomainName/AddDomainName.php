<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\DomainName;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\Webhosting\Space\AddDomainNameToSpaceForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddDomainName extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/domain-name/add', name: 'park_manager.admin.webhosting.space.domain_name.add', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, Space $space): Response
    {
        $form = $this->createForm(AddDomainNameToSpaceForm::class, options: ['space' => $space->id]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.domain_name_added'));

            return $this->redirectToRoute('park_manager.admin.webhosting.space.list_domain_names', ['space' => $space->id->toString()]);
        }

        return $this->render('admin/webhosting/domain_name/add.html.twig', ['form' => $form, 'space' => $space]);
    }
}
