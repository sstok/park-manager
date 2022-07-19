<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Pagerfanta;

use Pagerfanta\Adapter\AdapterInterface;
use ParkManager\Domain\ResultSet;
use Traversable;

/**
 * @implements AdapterInterface<mixed>
 */
final class ResultSetAdapter implements AdapterInterface
{
    /** @var ResultSet<mixed> */
    private ResultSet $resultSet;

    /**
     * @param ResultSet<mixed> $resultSet
     */
    public function __construct(ResultSet $resultSet)
    {
        $this->resultSet = $resultSet;
    }

    public function getNbResults(): int
    {
        return $this->resultSet->getNbResults();
    }

    /**
     * @return Traversable<array-key, mixed>
     */
    public function getSlice(int $offset, int $length): Traversable
    {
        $result = clone $this->resultSet;
        $result->setLimit($length, $offset);

        return $result->getIterator();
    }
}
