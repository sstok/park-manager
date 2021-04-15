<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\SubDomain;

use ParkManager\Domain\Webhosting\SubDomain\SubDomainRepository;

final class ActivateSubDomainHandler
{
    private SubDomainRepository $subDomainRepository;

    public function __construct(SubDomainRepository $subDomainRepository)
    {
        $this->subDomainRepository = $subDomainRepository;
    }

    public function __invoke(ActivateSubDomain $command): void
    {
        $subDomain = $this->subDomainRepository->get($command->id);
        $subDomain->activate();

        $this->subDomainRepository->save($subDomain);
    }
}
