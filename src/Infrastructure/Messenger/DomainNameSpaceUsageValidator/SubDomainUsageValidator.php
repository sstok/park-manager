<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

use ParkManager\Domain\DomainName\DomainName;
use ParkManager\Domain\Webhosting\Space\Space;
use ParkManager\Domain\Webhosting\SubDomain\SubDomain;
use ParkManager\Domain\Webhosting\SubDomain\SubDomainRepository;
use ParkManager\Infrastructure\Messenger\DomainNameSpaceUsageValidator;

final class SubDomainUsageValidator implements DomainNameSpaceUsageValidator
{
    private SubDomainRepository $subDomainRepository;

    public function __construct(SubDomainRepository $subDomainRepository)
    {
        $this->subDomainRepository = $subDomainRepository;
    }

    public function __invoke(DomainName $domainName, Space $space): array
    {
        $entities = [
            SubDomain::class => [],
        ];

        /** @var SubDomain $subDomain */
        foreach ($this->subDomainRepository->allFromSpace($space->id) as $subDomain) {
            if ($subDomain->host === $domainName) {
                $entities[SubDomain::class][] = $domainName;
            }
        }

        return $entities;
    }
}
