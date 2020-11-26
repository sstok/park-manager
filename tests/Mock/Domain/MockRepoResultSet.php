<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain;

use Doctrine\Common\Collections\ArrayCollection;
use ParkManager\Domain\ResultSet;

final class MockRepoResultSet implements ResultSet
{
    private array $result;
    private ?int $limit = null;
    private ?int $offset = null;
    private ?array $ordering = null;
    private ?array $limitedToIds = null;

    public function __construct(array $originalResult)
    {
        $this->result = $originalResult;
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
        \reset($this->result);
        $result = $this->result;

        if ($this->ordering) {
            [$orderField, $order] = $this->ordering;

            \usort($result, static function (object $a, object $b) use ($orderField, $order) {
                if ($a->{$orderField} === $b->{$orderField}) {
                    return 0;
                }

                if ($order === 'desc') {
                    return (string) $b->{$orderField} <=> (string) $a->{$orderField};
                }

                return (string) $a->{$orderField} <=> (string) $b->{$orderField};
            });
        }

        if ($this->offset) {
            $result = \array_slice($result, $this->offset, $this->limit);
        } elseif ($this->limit) {
            $result = \array_slice($result, 0, $this->limit);
        }

        if ($this->limitedToIds) {
            $result = \array_filter($result, fn (object $v) => \in_array($v->id->toString(), $this->limitedToIds, true));
        }

        return new ArrayCollection($result);
    }
}
