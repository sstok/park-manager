<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\UI\Web\Form\ChoiceList;

use ParkManager\Domain\ResultSet;
use Symfony\Component\Form\ChoiceList\Loader\AbstractChoiceLoader;

class ResultSetChoiceLoader extends AbstractChoiceLoader
{
    private ResultSet $resultSet;

    public function __construct(ResultSet $resultSet)
    {
        $this->resultSet = $resultSet;
    }

    protected function loadChoices(): iterable
    {
        $resultSet = clone $this->resultSet;
        $resultSet->limitToIds(null);

        return $resultSet;
    }

    /**
     * Returns the choices (entities) for the given $values.
     *
     * Note that only a sub-selection (with the $values) of
     * the entities is returned to reduce the memory usage.
     */
    protected function doLoadChoicesForValues(array $values, ?callable $value): array
    {
        $resultSet = clone $this->resultSet;
        $resultSet->limitToIds($values);
        $value ??= static fn (?object $object) => $object === null ? null : (string) $object->id;

        $objects = [];
        $objectsById = [];
        $values = array_filter($values);

        // Maintain order and indices from the given $values.
        foreach ($resultSet->limitToIds($values) as $object) {
            $objectsById[$value($object)] = $object;
        }

        foreach ($values as $i => $id) {
            if (isset($objectsById[$id])) {
                $objects[$i] = $objectsById[$id];
            }
        }

        return $objects;
    }
}
