<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service;

use Pdp\PublicSuffixList;
use Pdp\ResourceUri;
use Pdp\Storage\PublicSuffixListStorage;
use Pdp\Storage\TopLevelDomainListStorage;
use Pdp\TopLevelDomainList;

final class PdpManager
{
    public function __construct(
        private PublicSuffixListStorage $rulesStorage,
        private TopLevelDomainListStorage $topLevelDomainsStorage
    ) {
    }

    public function getPublicSuffixList(): PublicSuffixList
    {
        return $this->rulesStorage->get(ResourceUri::PUBLIC_SUFFIX_LIST_URI);
    }

    public function getTopLevelDomainList(): TopLevelDomainList
    {
        return $this->topLevelDomainsStorage->get(ResourceUri::TOP_LEVEL_DOMAIN_LIST_URI);
    }
}
