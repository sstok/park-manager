<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\DomainName;

use ParkManager\Domain\DomainName\Exception\DomainNameNotFound;
use ParkManager\Domain\User\User;
use ParkManager\Domain\User\UserId;
use ParkManager\Domain\Webhosting\Space\Exception\CannotRemoveActiveWebhostingSpace;

interface DomainNameRepository
{
    public function get(DomainNameId $id): DomainName;

    /**
     * @throws DomainNameNotFound
     */
    public function getByName(string $name, string $tld): DomainName;

    /**
     * @return iterable<DomainName>
     */
    public function allFromOwner(?UserId $userId): iterable;

    public function save(DomainName $domainName): void;

    /**
     * @throws CannotRemoveActiveWebhostingSpace When the related hosting space is still active
     */
    public function remove(DomainName $domainName): void;
}
