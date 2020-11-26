<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use ParkManager\Domain\ResultSet;

final class OrmQueryBuilderResultSet implements ResultSet
{
    private QueryBuilder $queryBuilder;
    private string $rootAlias;
    private ?int $limit = null;
    private ?int $offset = null;
    private ?array $ordering = null;
    private ?array $limitedToIds = null;

    public function __construct(QueryBuilder $queryBuilder, string $rootAlias)
    {
        $this->queryBuilder = $queryBuilder;
        $this->rootAlias = $rootAlias;
    }

    public function setLimit(?int $limit, ?int $offset = null): self
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    public function setOrdering(string $field, ?string $order): self
    {
        $this->ordering = [$field, $order];

        return $this;
    }

    public function limitToIds(?array $ids): self
    {
        $this->limitedToIds = $ids;

        return $this;
    }

    public function getIterator(): \Traversable
    {
        $queryBuilder = clone $this->queryBuilder;
        $queryBuilder->setMaxResults($this->limit);
        $queryBuilder->setFirstResult($this->offset);

        if ($this->ordering) {
            $queryBuilder->orderBy(...$this->ordering);
        }

        if ($this->limitedToIds) {
            $queryBuilder->andWhere(\sprintf('%s.id IN(:result_limited_ids)', $this->rootAlias));
            $queryBuilder->setParameter('result_limited_ids', $this->limitedToIds, Connection::PARAM_STR_ARRAY);
        }

        $result = $queryBuilder->getQuery()->getResult();

        return $result instanceof \Traversable ? $result : new ArrayCollection($result);
    }
}
