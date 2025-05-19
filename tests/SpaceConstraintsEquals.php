<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests;

use ParkManager\Domain\Webhosting\Constraint\Constraints;
use ParkManager\Tests\Domain\Webhosting\Constraint\ConstraintsComparator;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;

final class SpaceConstraintsEquals extends Constraint
{
    public function __construct(private Constraints $expected)
    {
    }

    public function toString(): string
    {
        return \sprintf('is equal to %s', $this->exporter()->export($this->expected));
    }

    /**
     * @param Constraints $other
     */
    protected function matches($other, string $description = '', bool $returnResult = false): bool
    {
        $comparatorFactory = new ComparatorFactory();
        $comparatorFactory->register(new ConstraintsComparator());

        try {
            $comparator = $comparatorFactory->getComparatorFor($this->expected, $other);
            $comparator->assertEquals($this->expected, $other);
        } catch (ComparisonFailure $f) {
            if ($returnResult) {
                return false;
            }

            throw new ExpectationFailedException(mb_trim($description . "\n" . $f->getMessage()), $f);
        }

        return true;
    }
}
