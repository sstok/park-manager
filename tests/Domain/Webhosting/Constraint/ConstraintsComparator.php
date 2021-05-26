<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\Constraint;

use ParkManager\Domain\Webhosting\Constraint\Constraints;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

final class ConstraintsComparator extends Comparator
{
    public function accepts($expected, $actual): bool
    {
        return \is_object($expected) && \is_object($actual) && $expected instanceof Constraints && $actual instanceof Constraints;
    }

    /**
     * @param Constraints $expected
     * @param Constraints $actual
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false): void
    {
        // Ignore the cause as this is to difficult to reproduce
        if ($expected->equals($actual)) {
            return;
        }

        $actual = clone $actual;
        $actual->changes = [];
        $actual->email->changes = [];
        $actual->database->changes = [];

        $expected = clone $expected;
        $expected->changes = [];
        $expected->email->changes = [];
        $expected->database->changes = [];

        throw new ComparisonFailure(
            $expected,
            $actual,
            $exportedExpected = $this->exporter->export($expected),
            $exportedActual = $this->exporter->export($actual),
            false,
            sprintf(
                'Failed asserting that %s equals expected %s.',
                $exportedActual,
                $exportedExpected
            )
        );
    }
}
