<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use ParkManager\Domain\Exception\OwnerNotFound;

interface OwnerRepository
{
    /**
     * @throws OwnerNotFound
     */
    public function get(OwnerId $id): Owner;

    public function save(Owner $owner): void;
}
