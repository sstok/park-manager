<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service;

use ParkManager\Domain\ResultSet;
use Traversable;

final class CombinedResultSet implements ResultSet
{
    private array $resultSets;
    private ?int $limit = null;
    private ?int $offset = null;
    private ?array $ordering = null;
    private ?array $limitedToIds = null;
    private ?array $iterators = null;
    private ?int $nbResults = null;

    public function __construct(ResultSet ...$resultSets)
    {
        $this->resultSets = $resultSets;
    }

    public function setLimit(?int $limit, ?int $offset = null): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->iterators = null;

        return $this;
    }

    public function setOrdering(?string $field, ?string $order): self
    {
        $this->ordering = $field === null ? null : [$field, $order];
        $this->iterators = null;

        return $this;
    }

    public function limitToIds(?array $ids): self
    {
        $this->limitedToIds = $ids;
        $this->iterators = null;

        return $this;
    }

    public function getNbResults(): int
    {
        $this->init();

        return $this->nbResults;
    }

    public function getIterator(): Traversable
    {
        $this->init();

        foreach ($this->iterators as $iterator) {
            foreach ($iterator as $row) {
                yield $row;
            }
        }
    }

    public function count(): int
    {
        return $this->getNbResults();
    }

    private function init(): void
    {
        if ($this->iterators !== null) {
            return;
        }

        $this->nbResults = 0;
        $this->iterators = [];

        foreach ($this->resultSets as $resultSet) {
            $resultSet = $resultSet
                ->setLimit($this->limit, $this->offset)
                ->setOrdering($this->ordering[0] ?? null, $this->ordering[1] ?? null)
                ->limitToIds($this->limitedToIds);

            $this->nbResults += $resultSet->getNbResults();
            $this->iterators[] = $resultSet->getIterator();
        }
    }
}
