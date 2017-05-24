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

namespace ParkManager\Common\Model;

/**
 * Marker interface for a collection of Entities (AggregateRoot objects).
 *
 * Each Collection interface must named after it's domain,
 * eg. UserCollection, WebhostingAccountCollection.
 *
 * Each collection must allow saving and getting entities,
 * the get() method may not throw exception for missing entities
 * but return null instead.
 *
 * * save(Entity $model): void
 * * get(IdValueObject $id): ?Entity
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface Collection
{
}
