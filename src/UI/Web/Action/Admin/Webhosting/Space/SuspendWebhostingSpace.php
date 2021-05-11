<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\UI\Web\Form\Type\Webhosting\Space\SuspendWebhostingSpaceForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class SuspendWebhostingSpace
{
    #[Route(path: 'webhosting/space/{space}/suspend-access', name: 'park_manager.admin.webhosting.space.suspend_access', methods: ['POST', 'GET'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory, Space $space): RouteRedirectResponse | TwigResponse
    {
        $form = $formFactory->create(SuspendWebhostingSpaceForm::class, $space);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.space.show', ['space' => $space->id])
                ->withFlash('success', 'flash.webhosting_space.access_suspended');
        }

        return new TwigResponse('admin/webhosting/space/suspend_access.html.twig', ['form' => $form->createView(), 'space' => $space]);
    }
}
