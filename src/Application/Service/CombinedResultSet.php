<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service;

use Doctrine\Common\Collections\Expr\Expression;
use Generator;
use ParkManager\Domain\ResultSet;
use Traversable;

/**
 * @template-implements ResultSet<mixed>
 */
final class CombinedResultSet implements ResultSet
{
    /** @var array<array-key, ResultSet<mixed>> */
    private array $resultSets;
    private ?int $limit = null;
    private ?int $offset = null;
    /** @var array<int, string>|null */
    private ?array $ordering = null;
    public ?Expression $expression = null;
    /** @var array<int, string|int>|null */
    private ?array $limitedToIds = null;
    /** @var array<string, bool> */
    private array $configChanged = ['filter' => false, 'limit' => false, 'order' => false, 'ids' => false];
    /** @var array<int, Traversable<mixed>>|null */
    private ?array $iterators = null;
    private ?int $nbResults = null;

    /**
     * @param ResultSet<mixed> ...$resultSets
     */
    public function __construct(ResultSet ...$resultSets)
    {
        $this->resultSets = $resultSets;
    }

    public function setLimit(?int $limit, ?int $offset = null): static
    {
        $this->limit = $limit;
        $this->offset = $offset;
        $this->iterators = null;

        $this->configChanged['limit'] = true;

        return $this;
    }

    public function setOrdering(?string $field, ?string $order): static
    {
        if ($field === null || $order === null) {
            $this->ordering = null;
        } else {
            $this->ordering = [$field, $order];
        }

        $this->configChanged['order'] = true;
        $this->iterators = null;

        return $this;
    }

    public function limitToIds(?array $ids): static
    {
        $this->configChanged['ids'] = true;
        $this->limitedToIds = $ids;
        $this->iterators = null;

        return $this;
    }

    public function getNbResults(): int
    {
        if ($this->iterators === null) {
            $this->init();
        }

        return $this->nbResults;
    }

    /**
     * @return Generator<int, mixed, mixed, void>
     */
    public function getIterator(): Traversable
    {
        if ($this->iterators === null) {
            $this->init();
        }

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
        $this->nbResults = 0;
        $this->iterators = [];

        foreach ($this->resultSets as $resultSet) {
            if ($this->configChanged['limit']) {
                $resultSet = $resultSet->setLimit($this->limit, $this->offset);
            }

            if ($this->configChanged['order']) {
                $resultSet = $resultSet->setOrdering($this->ordering[0] ?? null, $this->ordering[1] ?? null);
            }

            if ($this->configChanged['ids']) {
                $resultSet = $resultSet->limitToIds($this->limitedToIds);
            }

            if ($this->configChanged['filter']) {
                $resultSet = $resultSet->filter($this->expression);
            }

            $this->nbResults += $resultSet->getNbResults();
            $this->iterators[] = $resultSet->getIterator();
        }
    }

    public function filter(?Expression $expression): static
    {
        $this->expression = $expression;
        $this->configChanged['filter'] = true;
        $this->iterators = null;

        return $this;
    }
}
