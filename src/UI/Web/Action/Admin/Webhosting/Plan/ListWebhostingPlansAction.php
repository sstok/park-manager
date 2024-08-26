<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use Lifthill\Bridge\Web\Pagerfanta\ResultSetAdapter;
use Pagerfanta\Pagerfanta;
use ParkManager\Domain\Webhosting\Constraint\PlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ListWebhostingPlansAction extends AbstractController
{
    #[Route(path: 'webhosting/plan/', name: 'park_manager.admin.webhosting.plan.list', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request): Response
    {
        $pagerfanta = new Pagerfanta(new ResultSetAdapter($this->container->get(PlanRepository::class)->all()));
        $pagerfanta->setNormalizeOutOfRangePages(true);
        $pagerfanta->setMaxPerPage(10);

        $pagerfanta->setCurrentPage($request->query->getInt('page', 1));

        return $this->render('admin/webhosting/plan/list.html.twig', ['plans' => $pagerfanta]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [PlanRepository::class];
    }
}
