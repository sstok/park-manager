<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\Constraint;

use ArrayIterator;
use IteratorAggregate;
use ParkManager\Domain\Webhosting\Constraint\Exception\ConstraintNotInSet;
use Traversable;

final class Constraints implements IteratorAggregate
{
    /** @var Constraint[] */
    private $constraints = [];

    /** @var array[] */
    private $constraintsIndexed = [];

    public function __construct(Constraint ...$constraints)
    {
        foreach ($constraints as $constraint) {
            $constraintName = self::getConstraintName($constraint);

            $this->constraints[$constraintName] = $constraint;
            $this->constraintsIndexed[$constraintName] = $constraint->configuration();
        }

        \ksort($this->constraintsIndexed);
    }

    public function add(Constraint ...$constraints): self
    {
        // Cannot unpack array with string keys (class uniqueness is guarded by the constructor)
        return new self(...\array_merge(\array_values($this->constraints), $constraints));
    }

    public function remove(Constraint ...$constraints): self
    {
        $constraintsList = $this->constraints;

        foreach ($constraints as $constraint) {
            unset($constraintsList[self::getConstraintName($constraint)]);
        }

        return new self(...\array_values($constraintsList));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->constraints);
    }

    public function has(string $constraint): bool
    {
        return isset($this->constraints[$constraint]);
    }

    public function get(string $constraint): Constraint
    {
        if (! isset($this->constraints[$constraint])) {
            throw ConstraintNotInSet::withName($constraint);
        }

        return $this->constraints[$constraint];
    }

    public function toIndexedArray(): array
    {
        return $this->constraintsIndexed;
    }

    public function equals(self $other): bool
    {
        if ($this === $other) {
            return true;
        }

        // Leave values of the array are expected to be scalar. So strict comparison is possible.
        return $this->constraintsIndexed === $other->constraintsIndexed;
    }

    public static function getConstraintName(Constraint $constraint): string
    {
        $class = \get_class($constraint);

        $pos = \mb_strrpos($class, '\\');

        if ($pos === false) {
            return $class;
        }

        return \mb_substr($class, $pos + 1);
    }
}
