<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\DomainName;

use Pagerfanta\Pagerfanta;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Infrastructure\Pagerfanta\ResultSetAdapter;
use ParkManager\UI\Web\Response\TwigResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ListDomainNamesAction extends AbstractController
{
    #[Route(path: '/domain-names/', name: 'park_manager.admin.list_domain_names', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request): Response
    {
        $pagerfanta = new Pagerfanta(new ResultSetAdapter($this->get(DomainNameRepository::class)->all()));
        $pagerfanta->setNormalizeOutOfRangePages(true);
        $pagerfanta->setMaxPerPage(10);

        $pagerfanta->setCurrentPage($request->query->getInt('page', 1));

        return new TwigResponse('admin/domain_name/list.html.twig', ['domain_names' => $pagerfanta]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [DomainNameRepository::class];
    }
}
