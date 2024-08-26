<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use ParkManager\Application\Command\Webhosting\Constraint\SyncPlanConstraints;
use ParkManager\Domain\Translation\TranslatableMessage;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class SyncWebhostingPlanConstraintsAction extends AbstractController
{
    #[Route(path: 'webhosting/plan/{plan}/sync-constraints', name: 'park_manager.admin.webhosting.plan.sync_plan', methods: ['GET'])]
    public function __invoke(Request $request, Plan $plan, MessageBusInterface $messageBus): Response
    {
        $tokenId = 'sync-plan' . $plan->id->toString();
        $token = (string) $request->query->get('token', '');

        if (! $this->isCsrfTokenValid($tokenId, $token)) {
            $this->addFlash('error', new TranslatableMessage('flash.invalid_token_provided'));

            return $this->redirectToRoute('park_manager.admin.webhosting.plan.show', ['plan' => $plan->id->toString()]);
        }

        $this->container->get('security.csrf.token_manager')->removeToken($tokenId);
        $messageBus->dispatch(new SyncPlanConstraints($plan->id));

        $usedBySpacesNb = $this->container->get(SpaceRepository::class)->allWithAssignedPlan($plan->id)->getNbResults();
        $this->addFlash('success', new TranslatableMessage('flash.webhosting_plan.assignment_update_dispatched', ['spaces_count' => $usedBySpacesNb]));

        return $this->redirectToRoute('park_manager.admin.webhosting.plan.show', ['plan' => $plan->id->toString()]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [SpaceRepository::class];
    }
}
