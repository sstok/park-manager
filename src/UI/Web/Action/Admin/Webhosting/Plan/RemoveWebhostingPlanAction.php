<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use ParkManager\Application\Command\Webhosting\Constraint\RemovePlan;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceRepository;
use ParkManager\UI\Web\Form\Type\ConfirmationForm;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;

final class RemoveWebhostingPlanAction extends AbstractController
{
    #[Route(path: 'webhosting/plan/{plan}/remove', name: 'park_manager.admin.webhosting.plan.remove', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory, Plan $plan): RouteRedirectResponse | TwigResponse
    {
        $usedBySpacesNb = $this->get(WebhostingSpaceRepository::class)->allWithAssignedPlan($plan->id)->getNbResults();

        $form = $formFactory->create(ConfirmationForm::class, null, [
            'confirmation_title' => new TranslatableMessage('webhosting.plan.remove.heading', ['id' => $plan->id->toString()]),
            'confirmation_message' => new TranslatableMessage('webhosting.plan.remove.confirm_warning', ['assignment_count' => $usedBySpacesNb]),
            'confirmation_label' => 'label.remove',
            'cancel_route' => 'park_manager.admin.webhosting.plan.list',
            'command_factory' => static fn () => new RemovePlan($plan->id),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.plan.list', ['plan' => $plan->id->toString()])
                ->withFlash('success', 'flash.webhosting_plan.removed')
            ;
        }

        return new TwigResponse('admin/webhosting/plan/remove.html.twig', ['form' => $form->createView(), 'plan' => $plan]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [WebhostingSpaceRepository::class];
    }
}
