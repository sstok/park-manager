<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Email;

use ParkManager\Domain\DomainName\DomainNamePair;
use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\Email\Exception\EmailForwardNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;

interface ForwardRepository
{
    /**
     * @throws EmailForwardNotFound
     */
    public function get(ForwardId $id): Forward;

    /**
     * @throws EmailForwardNotFound
     */
    public function getByName(string $address, DomainNamePair $domainNamePair): Forward;

    /**
     * @return ResultSet<Forward>
     */
    public function allBySpace(SpaceId $space): ResultSet;

    public function countBySpace(SpaceId $space): int;

    public function save(Forward $forward): void;

    public function remove(Forward $forward): void;
}
