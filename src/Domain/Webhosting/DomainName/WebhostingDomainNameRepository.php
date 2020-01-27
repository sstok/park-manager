<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\DomainName;

use ParkManager\Domain\Webhosting\DomainName;
use ParkManager\Domain\Webhosting\DomainName\Exception\WebhostingDomainNameNotFound;
use ParkManager\Domain\Webhosting\Space\Exception\WebhostingSpaceNotFound;
use ParkManager\Domain\Webhosting\Space\WebhostingSpaceId;

interface WebhostingDomainNameRepository
{
    /**
     * @throws WebhostingDomainNameNotFound When no domain-name was found with the id
     */
    public function get(WebhostingDomainNameId $id): WebhostingDomainName;

    /**
     * @throws WebhostingSpaceNotFound When no space was found with the id
     */
    public function getPrimaryOf(WebhostingSpaceId $id): WebhostingDomainName;

    public function findByFullName(DomainName $name): ?WebhostingDomainName;

    /**
     * Note: Only one DomainName _per webhosting space_ can be marked primary,
     * any previously assigned primary domain name must have the primary
     * marking removed.
     */
    public function save(WebhostingDomainName $domainName): void;

    /**
     * Remove an webhosting domain-name registration from the repository.
     *
     * An WebhostingDomainName that's marked as primary cannot
     * be deleted (unless the space is marked for removal).
     */
    public function remove(WebhostingDomainName $domainName): void;
}
