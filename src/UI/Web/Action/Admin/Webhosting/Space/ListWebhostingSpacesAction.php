<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use Pagerfanta\Pagerfanta;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;
use ParkManager\Infrastructure\Pagerfanta\ResultSetAdapter;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ListWebhostingSpacesAction extends AbstractController
{
    #[Route(path: 'webhosting/space/', name: 'park_manager.admin.webhosting.space.list', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request): TwigResponse
    {
        $pagerfanta = new Pagerfanta(new ResultSetAdapter($this->get(SpaceRepository::class)->all()));
        $pagerfanta->setNormalizeOutOfRangePages(true);
        $pagerfanta->setMaxPerPage(10);

        $pagerfanta->setCurrentPage($request->query->getInt('page', 1));

        return new TwigResponse('admin/webhosting/space/list.html.twig', ['spaces' => $pagerfanta]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [SpaceRepository::class];
    }
}
