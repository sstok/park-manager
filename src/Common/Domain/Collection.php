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

namespace ParkManager\Common\Domain;

/**
 * Marker interface for a collection of entities.
 *
 * Each collection must allow adding/removing,
 * and finding by unique value (id), or criteria.
 *
 * Note: Transaction handling is performed outside of the
 * collection, saving an entity must not commit the transaction.
 *
 * * add(Entity $model)
 * * save(Entity $model)
 * * delete(Entity $model)
 *
 * * find(IdVO $id): Entity
 * * all(): Entity[] (iterable)
 * * byCriteria(Criteria $criteria): Entity[] (iterable)
 *
 * @author Sebastiaan Stok <s.stok@rollerworks.net>
 */
interface Collection
{
}
