<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Domain\DomainName;

use ParkManager\Module\WebhostingModule\Domain\Account\Exception\WebhostingAccountNotFound;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\DomainName;
use ParkManager\Module\WebhostingModule\Domain\DomainName\Exception\WebhostingDomainNameNotFound;

interface WebhostingDomainNameRepository
{
    /**
     * @throws WebhostingDomainNameNotFound when no domain-name was found with the id
     */
    public function get(WebhostingDomainNameId $id): WebhostingDomainName;

    /**
     * Get the primary DomainName of an webhosting account.
     *
     * @throws WebhostingAccountNotFound when no account was found with the id
     */
    public function getPrimaryOf(WebhostingAccountId $id): WebhostingDomainName;

    /**
     * Finds a WebhostingDomainName registration by it's full name.
     */
    public function findByFullName(DomainName $name): ?WebhostingDomainName;

    /**
     * Save the WebhostingDomainName in the repository.
     *
     * This will either store a new account or update an existing one.
     *
     * Note: Only one DomainName _per webhosting account_ can be marked primary,
     * any previously assigned primary domain name must have the primary
     * marking removed.
     */
    public function save(WebhostingDomainName $domainName): void;

    /**
     * Remove an webhosting domain-name registration from the repository.
     *
     * An WebhostingDomainName that's marked as primary cannot
     * be deleted (unless the account is marked for removal).
     */
    public function remove(WebhostingDomainName $domainName): void;
}
