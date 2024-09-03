<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Plan;

use Lifthill\Component\Datagrid\DatagridFactory;
use Lifthill\Component\Datagrid\Extension\Core\Type\DateTimeType;
use ParkManager\Domain\Webhosting\Constraint\Plan;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShowWebhostingPlan extends AbstractController
{
    #[Route(path: 'webhosting/plan/{plan}/', name: 'park_manager.admin.webhosting.plan.show', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request, Plan $plan, SpaceRepository $repository, DatagridFactory $datagridFactory): Response
    {
        $spaces = $repository->allWithAssignedPlan($plan->id);

        $datagrid = $datagridFactory->createDatagridBuilder(false)
            ->add('name', options: [
                'label' => 'label.name',
                'search_type' => TextType::class,
                'sortable' => true,
                'data_provider' => 'primaryDomainLabel',
            ])
            ->add('registeredAt', DateTimeType::class, options: [
                'label' => 'label.registered_on',
                'time_format' => \IntlDateFormatter::SHORT,

                'search_type' => \Rollerworks\Component\Search\Extension\Core\Type\DateTimeType::class,
                'sortable' => true,
            ])
            ->limits(default: 10)
            ->getDatagrid($spaces)
        ;

        $datagrid->handleRequest($request);

        if ($datagrid->isChanged()) {
            return $this->redirectToRoute('park_manager.admin.list_users', [$datagrid->getName() => $datagrid->getQueryArguments()]);
        }

        $usedBySpacesNb = $repository->allWithAssignedPlan($plan->id)->getNbResults();

        return $this->render('admin/webhosting/plan/show.html.twig', [
            'plan' => $plan,
            'spaces_count' => $usedBySpacesNb,
            'spaces' => $datagrid->createView(),
        ]);
    }
}
