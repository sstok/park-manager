<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\DomainName;

use Lifthill\Bridge\Web\Pagerfanta\ResultSetAdapter;
use Pagerfanta\Pagerfanta;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ListDomainNamesAction extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/domain-name/', name: 'park_manager.admin.webhosting.space.list_domain_names', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request, Space $space): Response
    {
        $pagerfanta = new Pagerfanta(new ResultSetAdapter($this->container->get(DomainNameRepository::class)->allFromSpace($space->id)->setOrdering('d.primary', 'desc')));
        $pagerfanta->setNormalizeOutOfRangePages(true);
        $pagerfanta->setMaxPerPage(10);

        $pagerfanta->setCurrentPage($request->query->getInt('page', 1));

        return $this->render('admin/webhosting/domain_name/list.html.twig', ['domain_names' => $pagerfanta, 'space' => $space]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [DomainNameRepository::class];
    }
}
