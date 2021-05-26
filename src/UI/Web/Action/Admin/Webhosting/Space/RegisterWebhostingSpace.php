<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use ParkManager\UI\Web\Form\Type\Webhosting\Space\RegisterWebhostingSpaceForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class RegisterWebhostingSpace
{
    #[Route(path: 'webhosting/space/register', name: 'park_manager.admin.webhosting.space.register', methods: ['POST', 'GET'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory): RouteRedirectResponse | TwigResponse
    {
        $form = $formFactory->create(RegisterWebhostingSpaceForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.space.list')
                ->withFlash('success', 'flash.webhosting_space.registered')
            ;
        }

        return new TwigResponse('admin/webhosting/space/register.html.twig', $form);
    }
}
