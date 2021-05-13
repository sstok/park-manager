<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Doctrine\Common\Collections\Expr\Expression;

/**
 * A ResultSet functions as a proxy between a repository,
 * and the application/UI.
 *
 * Allowing to change how the collection is presented.
 */
interface ResultSet extends \IteratorAggregate, \Countable
{
    public function setLimit(?int $limit, ?int $offset = null): self;

    /**
     * @param string $field an entity field-name
     * @param string $order either asc or desc
     */
    public function setOrdering(?string $field, ?string $order): self;

    /**
     * Apply a filtering expression on the result.
     *
     * This should (internally) be applied before limitToIds().
     */
    public function filter(?Expression $expression): self;

    /**
     * Pass an array of entity IDs to limit the returned result
     * to only the IDs in the original collection.
     *
     * @param array<int,string>|null $ids
     */
    public function limitToIds(?array $ids): self;

    /**
     * Returns the number of items in the set.
     */
    public function getNbResults(): int;
}
