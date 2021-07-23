<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use ParkManager\Application\Command\Webhosting\Constraint\SyncPlanConstraints;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\UI\Web\Response\RouteRedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class SyncWebhostingPlanConstraintsAction extends AbstractController
{
    #[Route(path: 'webhosting/plan/{plan}/sync-constraints', name: 'park_manager.admin.webhosting.plan.sync_plan', methods: ['GET'])]
    public function __invoke(Request $request, FormFactoryInterface $formFactory, Plan $plan): RouteRedirectResponse
    {
        $tokenId = 'sync-plan' . $plan->id->toString();
        $token = (string) $request->query->get('token', '');

        if (! $this->isCsrfTokenValid($tokenId, $token)) {
            return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.plan.show', ['plan' => $plan->id->toString()])
                ->withFlash(type: 'error', message: 'flash.invalid_token_provided')
            ;
        }

        $this->container->get('security.csrf.token_manager')->removeToken($tokenId);
        $this->dispatchMessage(new SyncPlanConstraints($plan->id));

        $usedBySpacesNb = $this->get(SpaceRepository::class)->allWithAssignedPlan($plan->id)->getNbResults();

        return RouteRedirectResponse::toRoute('park_manager.admin.webhosting.plan.show', ['plan' => $plan->id->toString()])
            ->withFlash(type: 'success', message: 'flash.webhosting_plan.assignment_update_dispatched', arguments: ['spaces_count' => $usedBySpacesNb])
        ;
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [SpaceRepository::class];
    }
}
