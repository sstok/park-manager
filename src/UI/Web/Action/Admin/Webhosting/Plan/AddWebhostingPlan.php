<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\UI\Web\Form\Type\Webhosting\Plan\AddWebhostingPlanForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AddWebhostingPlan extends AbstractController
{
    #[Route(path: 'webhosting/plan/add', name: 'park_manager.admin.webhosting.plan.add', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(AddWebhostingPlanForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.webhosting_plan.added'));

            return $this->redirectToRoute('park_manager.admin.webhosting.plan.list');
        }

        return $this->render('admin/webhosting/plan/add.html.twig', ['form' => $form]);
    }
}
