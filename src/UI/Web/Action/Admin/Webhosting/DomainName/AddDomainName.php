<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\DomainName;

use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\Webhosting\Space\AddDomainNameToSpaceForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class AddDomainName
{
    #[Route(path: 'webhosting/space/{space}/domain-name/add', name: 'park_manager.admin.webhosting.space.domain_name.add', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory, Space $space): RouteRedirectResponse | TwigResponse
    {
        $form = $formFactory->create(AddDomainNameToSpaceForm::class, options: ['space' => $space->id]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.space.list_domain_names', ['space' => $space->id])->withFlash(type: 'success', message: 'flash.domain_name_added');
        }

        return new TwigResponse('admin/webhosting/domain_name/add.html.twig', ['form' => $form->createView(), 'space' => $space]);
    }
}
