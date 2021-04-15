<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\SubDomain;

use ParkManager\Domain\Webhosting\SubDomain\SubDomainRepository;

final class DeactivateSubDomainHandler
{
    private SubDomainRepository $subDomainRepository;

    public function __construct(SubDomainRepository $subDomainRepository)
    {
        $this->subDomainRepository = $subDomainRepository;
    }

    public function __invoke(DeactivateSubDomain $command): void
    {
        $subDomain = $this->subDomainRepository->get($command->id);
        $subDomain->deActivate();

        $this->subDomainRepository->save($subDomain);
    }
}
