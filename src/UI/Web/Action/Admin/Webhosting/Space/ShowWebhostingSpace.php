<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Action\Admin\Webhosting\Space;

use ParkManager\Application\Service\StorageUsage;
use ParkManager\Domain\ByteSize;
use ParkManager\Domain\DomainName\DomainNameRepository;
use ParkManager\Domain\Webhosting\Space\Space;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ShowWebhostingSpace extends AbstractController
{
    #[Route(path: 'webhosting/space/{space}/', name: 'park_manager.admin.webhosting.space.show', methods: ['GET', 'HEAD'])]
    public function __invoke(Request $request, Space $space): Response
    {
        $primary = $this->get(DomainNameRepository::class)->getPrimaryOf($space->id);
        $domainCount = $this->get(DomainNameRepository::class)->allFromSpace($space->id)->getNbResults();

        $diskUsage = $this->get(StorageUsage::class)->getDiskUsageOf($space->id);
        $trafficUsage = new ByteSize(5, 'GiB');

        return $this->render('admin/webhosting/space/show.html.twig', [
            'space' => $space,
            'domain_name' => $primary,
            'domain_names_count' => $domainCount,
            'disk_usage' => $diskUsage,
            'traffic_usage' => $trafficUsage,
        ]);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [DomainNameRepository::class, StorageUsage::class];
    }
}
