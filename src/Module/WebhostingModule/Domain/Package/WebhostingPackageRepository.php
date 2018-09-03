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

namespace ParkManager\Module\WebhostingModule\Domain\Package;

use ParkManager\Module\WebhostingModule\Domain\Package\Exception\WebhostingPackageNotFound;

interface WebhostingPackageRepository
{
    /**
     * @throws WebhostingPackageNotFound when no package was found with the id
     */
    public function get(WebhostingPackageId $id): WebhostingPackage;

    /**
     * Save the WebhostingPackage in the repository.
     *
     * This will either store a new package or update an existing one.
     */
    public function save(WebhostingPackage $package): void;

    /**
     * Remove an webhosting package from the repository.
     */
    public function remove(WebhostingPackage $package): void;
}
