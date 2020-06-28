<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName;

use ParkManager\Domain\DomainName\Exception\CannotRemovePrimaryDomainName;
use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;

interface DomainNameRepository
{
    /**
     * @throws DomainNameNotFound When no domain-name was found with the id
     */
    public function get(DomainNameId $id): DomainName;

    /**
     * @throws WebhostingSpaceNotFound When no space was found with the id
     */
    public function getPrimaryOf(SpaceId $id): DomainName;

    /**
     * @throws DomainNameNotFound
     */
    public function getByName(DomainNamePair $name): DomainName;

    /**
     * @return iterable<DomainName>
     */
    public function allFromOwner(?UserId $userId): iterable;

    /**
     * @return iterable<DomainName>
     */
    public function allAccessibleBy(?UserId $userId): iterable;

    /**
     * @return iterable<DomainName>
     */
    public function allFromSpace(SpaceId $id): iterable;

    public function save(DomainName $domainName): void;

    /**
     * @throws CannotRemovePrimaryDomainName When the related hosting space is still active
     */
    public function remove(DomainName $domainName): void;
}
