<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;
use ParkManager\UI\Web\Form\Type\Webhosting\Plan\EditWebhostingPlanForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class EditWebhostingPlan extends AbstractController
{
    #[Route(path: 'webhosting/plan/{plan}/edit', name: 'park_manager.admin.webhosting.plan.edit', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory, Plan $plan): RouteRedirectResponse | TwigResponse
    {
        $usedBySpacesNb = $this->get(WebhostingSpaceRepository::class)->allWithAssignedPlan($plan->id)->getNbResults();

        $form = $formFactory->create(EditWebhostingPlanForm::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = RouteRedirectResponse::toRoute('park_manager.admin.webhosting.plan.show', ['plan' => $plan->id->toString()]);
            $response->withFlash(type: 'success', message: 'flash.webhosting_plan.updated');

            if ($form->get('updateLinked')->getData()) {
                $response->withFlash(type: 'info', message: 'flash.webhosting_plan.assignment_update_dispatched', arguments: ['spaces_count' => $usedBySpacesNb]);
            }

            return $response;
        }

        return new TwigResponse('admin/webhosting/plan/edit.html.twig', [
            'form' => $form->createView(),
            'plan' => $plan,
            'spaces_count' => $usedBySpacesNb,
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [WebhostingSpaceRepository::class];
    }
}
