<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\SubDomain;

use Lifthill\Component\Common\Domain\Attribute\Repository;
use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Domain\Webhosting\SubDomain\Exception\SubDomainAlreadyExists;
use ParkManager\Domain\Webhosting\SubDomain\Exception\SubDomainNotFound;

#[Repository]
interface SubDomainRepository
{
    /**
     * @throws SubDomainNotFound
     */
    public function get(SubDomainNameId $id): SubDomain;

    /**
     * @return ResultSet<SubDomain>
     */
    public function allFromSpace(SpaceId $space): ResultSet;

    /**
     * @throws SubDomainAlreadyExists
     */
    public function save(SubDomain $subDomain): void;

    public function remove(SubDomain $subDomain): void;
}
