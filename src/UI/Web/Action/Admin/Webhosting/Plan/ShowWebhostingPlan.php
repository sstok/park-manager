<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowWebhostingPlan extends AbstractController
{
    #[Route(path: 'webhosting/plan/{plan}/', name: 'park_manager.admin.webhosting.plan.show', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request, Plan $plan): Response
    {
        $usedBySpacesNb = $this->container->get(SpaceRepository::class)->allWithAssignedPlan($plan->id)->getNbResults();

        return $this->render('admin/webhosting/plan/show.html.twig', [
            'plan' => $plan,
            'spaces_count' => $usedBySpacesNb,
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [SpaceRepository::class];
    }
}
