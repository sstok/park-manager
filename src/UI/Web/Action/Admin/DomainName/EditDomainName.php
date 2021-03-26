<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\DomainName;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\UI\Web\Form\Type\DomainName\EditDomainNameForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class EditDomainName
{
    #[Route(path: 'domain-name/{domainName}/edit/', name: 'park_manager.admin.domain_name.edit', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory, DomainName $domainName): RouteRedirectResponse | TwigResponse
    {
        $form = $formFactory->create(EditDomainNameForm::class, $domainName);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.list_domain_names')->withFlash(type: 'success', message: 'flash.domain_name_changed');
        }

        return new TwigResponse('admin/domain_name/edit.html.twig', ['form' => $form->createView(), 'domainName' => $domainName]);
    }
}
