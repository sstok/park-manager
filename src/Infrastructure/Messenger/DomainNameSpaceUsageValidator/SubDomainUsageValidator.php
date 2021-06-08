<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

use Doctrine\Common\Collections\Criteria;
use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainRepository;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

final class SubDomainUsageValidator implements DomainNameSpaceUsageValidator
{
    public function __construct(private SubDomainRepository $subDomainRepository)
    {
    }

    public function __invoke(DomainName $domainName, Space $space): array
    {
        return [
            SubDomain::class => $this->subDomainRepository->allFromSpace($space->id)
                ->filter(Criteria::expr()->eq('host', $domainName))
                ->setLimit(20),
        ];
    }
}
