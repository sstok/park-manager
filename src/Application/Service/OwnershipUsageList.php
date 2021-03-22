<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service;

use Assert\Assertion;
use ParkManager\Domain\OwnerControlledRepository;
use ParkManager\Domain\OwnerId;
use ParkManager\Domain\ResultSet;

final class OwnershipUsageList
{
    /** @var array<string, OwnerControlledRepository> */
    private array $repositories;

    /**
     * @param array<string, OwnerControlledRepository> $repositories ['{RootEntityFullName}' => {SpaceRepository}]
     */
    public function __construct(array $repositories)
    {
        Assertion::allIsInstanceOf($repositories, OwnerControlledRepository::class);

        $this->repositories = $repositories;
    }

    /**
     * Returns whether at least one repository has an entity that is owned by the Owner{Id}.
     *
     * Stops on first not-empty result, and should be used when the result doesn't matter.
     */
    public function isAnyAssignedTo(OwnerId $id): bool
    {
        foreach ($this->repositories as $name => $repository) {
            // Ordering doesn't matter, so remove this to provide some Query optimization.
            $iterator = $repository->allFromOwner($id)
                ->setOrdering(null, null);

            if ($iterator->getNbResults() > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns all the entities (from all repositories) in an Aggregate collection.
     *
     * The ResultSet does not return a single-type Entity, but depending
     * on the repository.
     *
     * @return ResultSet<object>
     */
    public function getAllEntities(OwnerId $id): ResultSet
    {
        $resultSets = [];

        foreach ($this->repositories as $repository) {
            $resultSets[] = $repository->allFromOwner($id);
        }

        return new CombinedResultSet(...$resultSets);
    }

    /**
     * Returns all the entities (from all repositories) in an Aggregate collection
     * combined per "type", either ["\ParkManager\Domain\Webhosting\Space\Space" => ResultSet<Space>].
     *
     * @return array<string, ResultSet<object>>
     */
    public function getByProvider(OwnerId $id): array
    {
        $resultSets = [];

        foreach ($this->repositories as $name => $repository) {
            $resultSets[$name] = $repository->allFromOwner($id);
        }

        return $resultSets;
    }
}
