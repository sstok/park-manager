<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\DomainName;

use ParkManager\Domain\DomainName\DomainNameRepository;

final class RemoveDomainNameHandler
{
    private DomainNameRepository $domainNameRepository;

    public function __construct(DomainNameRepository $domainNameRepository)
    {
        $this->domainNameRepository = $domainNameRepository;
    }

    public function __invoke(RemoveDomainName $command): void
    {
        $domainName = $this->domainNameRepository->get($command->id);

        // Validation of Space primary and usage is handled automatically.
        $this->domainNameRepository->remove($domainName);
    }
}
