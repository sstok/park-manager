<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use ParkManager\Application\Command\Webhosting\Constraint\RemovePlan;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RemoveWebhostingPlanAction extends AbstractController
{
    #[Route(path: 'webhosting/plan/{plan}/remove', name: 'park_manager.admin.webhosting.plan.remove', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, Plan $plan): Response
    {
        $usedBySpacesNb = $this->get(SpaceRepository::class)->allWithAssignedPlan($plan->id)->getNbResults();

        $form = $this->createForm(ConfirmationForm::class, null, [
            'confirmation_title' => new TranslatableMessage('webhosting.plan.remove.heading', ['id' => $plan->id->toString()]),
            'confirmation_message' => new TranslatableMessage('webhosting.plan.remove.confirm_warning', ['assignment_count' => $usedBySpacesNb]),
            'confirmation_label' => 'label.remove',
            'cancel_route' => 'park_manager.admin.webhosting.plan.list',
            'command_factory' => static fn () => new RemovePlan($plan->id),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('success', new TranslatableMessage('flash.webhosting_plan.removed'));

            return $this->redirectToRoute('park_manager.admin.webhosting.plan.list', ['plan' => $plan->id->toString()]);
        }

        return $this->renderForm('admin/webhosting/plan/remove.html.twig', ['form' => $form, 'plan' => $plan]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [SpaceRepository::class];
    }
}
