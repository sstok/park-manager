<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

/**
 * A repository implementing this interface indicates that the root entity
 * has an Owner relation.
 *
 * @template T
 */
interface OwnerControlledRepository
{
    /**
     * @return ResultSet<T>
     */
    public function allFromOwner(OwnerId $id): ResultSet;
}
