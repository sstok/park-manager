<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine;

use ArrayIterator;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use ParkManager\Domain\ResultSet;

/**
 * @template T
 * @implements ResultSet<T>
 */
final class OrmQueryBuilderResultSet implements ResultSet
{
    private ?int $length = null;
    private ?int $offset = null;
    /** @var array<int, string>|null */
    private ?array $ordering = null;
    private ?Expression $expression = null;
    /** @var array<int, string|int>|null */
    private ?array $limitedToIds = null;
    /** @var Paginator<T>|null */
    private ?Paginator $paginator = null;

    /**
     * @param bool $fetchJoinCollection whether the query joins a collection (true by default), set
     *                                  to false when not used to speed-up pagination
     */
    public function __construct(
        private QueryBuilder $queryBuilder,
        private string $rootAlias,
        private bool $fetchJoinCollection = true
    ) {
    }

    public function setLimit(?int $limit, ?int $offset = null): static
    {
        $this->length = $limit;
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

        $this->paginator = null;

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
        $this->paginator = null;

        return $this;
    }

    public function getNbResults(): int
    {
        return \count($this->getPaginator());
    }

    /**
     * @return ArrayIterator<array-key, T>
     */
    public function getIterator(): ArrayIterator
    {
        $paginator = $this->getPaginator();

        // Returns the actual query used for paging so we can safely set the length and offset
        // without messing-up the query-builder, plus/ we don't have to reconstruct
        // the Paginator when the length/offset changes.
        $query = $paginator->getQuery();
        $query->setMaxResults($this->length);
        $query->setFirstResult($this->offset);

        return $this->paginator->getIterator();
    }

    public function count(): int
    {
        return $this->getNbResults();
    }

    /**
     * @return Paginator<T>
     */
    private function getPaginator(): Paginator
    {
        if (isset($this->paginator)) {
            return $this->paginator;
        }

        $queryBuilder = clone $this->queryBuilder;

        if ($this->ordering) {
            $queryBuilder->orderBy(...$this->ordering);
        }

        if ($this->expression) {
            $queryBuilder->addCriteria(new Criteria($this->expression));
        }

        if ($this->limitedToIds) {
            $queryBuilder->andWhere(sprintf('%s.id IN(:result_limited_ids)', $this->rootAlias));
            $queryBuilder->setParameter('result_limited_ids', $this->limitedToIds, Connection::PARAM_STR_ARRAY);
        }

        return $this->paginator = new Paginator($queryBuilder, $this->fetchJoinCollection);
    }
}
