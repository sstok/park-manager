<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use ParkManager\Domain\ResultSet;
use Traversable;

/**
 * @template T
 * @template-implements ResultSet<T>
 */
final class MockRepoResultSet implements ResultSet
{
    /** @var array<array-key, T> */
    private array $result;
    public ?int $limit = null;
    public ?int $offset = null;
    /** @var array<int, string>|null */
    public ?array $ordering = null;
    public ?Expression $expression = null;
    /** @var array<int, string|int>|null */
    public ?array $limitedToIds = null;

    /**
     * @param array<array-key, T> $originalResult
     */
    public function __construct(array $originalResult = [])
    {
        $this->result = $originalResult;
    }

    public function setLimit(?int $limit, ?int $offset = null): static
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    public function setOrdering(?string $field, ?string $order): static
    {
        if ($field === null || $order === null) {
            $this->ordering = null;
        } else {
            $this->ordering = [$field, $order];
        }

        return $this;
    }

    public function filter(?Expression $expression): static
    {
        $this->expression = $expression;

        return $this;
    }

    public function limitToIds(?array $ids): static
    {
        $this->limitedToIds = $ids;

        return $this;
    }

    public function getNbResults(): int
    {
        reset($this->result);
        $result = $this->result;

        if ($this->expression) {
            $result = new ArrayCollection($result);
            $result = $result->matching(new Criteria($this->expression))->toArray();
        }

        if ($this->limitedToIds) {
            $result = array_filter($result, fn (object $v): bool => \in_array($v->id->toString(), $this->limitedToIds, true));
        }

        return \count($result);
    }

    /**
     * @return ArrayCollection<array-key, T>
     */
    public function getIterator(): Traversable
    {
        reset($this->result);
        $result = $this->result;

        // Don't apply the order at ArrayCollection as we need to cast the values to string.
        if ($this->ordering[0] ?? false) {
            [$orderField, $order] = $this->ordering;

            usort($result, static function (object $a, object $b) use ($orderField, $order): int {
                if ($a->{$orderField} === $b->{$orderField}) {
                    return 0;
                }

                if ($order === 'desc') {
                    return (string) $b->{$orderField} <=> (string) $a->{$orderField};
                }

                return (string) $a->{$orderField} <=> (string) $b->{$orderField};
            });
        }

        $result = new ArrayCollection($result);
        $criteria = new Criteria();
        $hasCriteria = false;

        if ($this->expression) {
            $criteria->where($this->expression);
            $hasCriteria = true;
        }

        if ($this->offset) {
            $criteria->setFirstResult($this->offset);
            $hasCriteria = true;
        }

        if ($this->limit) {
            $criteria->setMaxResults($this->limit);
            $hasCriteria = true;
        }

        if ($this->limitedToIds) {
            $criteria->andWhere(Criteria::expr()->in('id', $this->limitedToIds));
            $hasCriteria = true;
        }

        if ($hasCriteria) {
            $result = $result->matching($criteria);
        }

        return $result;
    }

    public function count(): int
    {
        return $this->getNbResults();
    }
}
