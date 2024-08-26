<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\UI\Web\Form\Type\Webhosting\Plan\EditWebhostingPlanForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EditWebhostingPlan extends AbstractController
{
    #[Route(path: 'webhosting/plan/{plan}/edit', name: 'park_manager.admin.webhosting.plan.edit', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, Plan $plan): Response
    {
        $usedBySpacesNb = $this->container->get(SpaceRepository::class)->allWithAssignedPlan($plan->id)->getNbResults();

        $form = $this->createForm(EditWebhostingPlanForm::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.webhosting_plan.updated'));

            if ($form->get('updateLinked')->getData()) {
                $this->addFlash('info', new TranslatableMessage('flash.webhosting_plan.assignment_update_dispatched', ['spaces_count' => $usedBySpacesNb]));
            }

            return $this->redirectToRoute('park_manager.admin.webhosting.plan.show', ['plan' => $plan->id->toString()]);
        }

        return $this->render('admin/webhosting/plan/edit.html.twig', [
            'form' => $form,
            'plan' => $plan,
            'spaces_count' => $usedBySpacesNb,
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [SpaceRepository::class];
    }
}
