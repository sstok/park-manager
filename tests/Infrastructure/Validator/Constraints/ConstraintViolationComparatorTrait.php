<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;
use Symfony\Component\Validator\ConstraintViolation;

trait ConstraintViolationComparatorTrait
{
    private static $violationComparator;

    /**
     * @beforeClass
     */
    public static function setUpValidatorComparator(): void
    {
        self::$violationComparator = new ConstraintViolationComparator();

        $comparatorFactory = ComparatorFactory::getInstance();
        $comparatorFactory->register(self::$violationComparator);
    }

    /**
     * @afterClass
     */
    public static function tearDownValidatorComparator(): void
    {
        $comparatorFactory = ComparatorFactory::getInstance();
        $comparatorFactory->unregister(self::$violationComparator);
    }
}

final class ConstraintViolationComparator extends Comparator
{
    public function accepts($expected, $actual): bool
    {
        if (! \is_object($expected) || ! \is_object($actual)) {
            return false;
        }

        return $expected instanceof ConstraintViolation && $actual instanceof ConstraintViolation;
    }

    /**
     * @param ConstraintViolation $expected
     * @param ConstraintViolation $actual
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false): void
    {
        $this->factory->getComparatorFor($expected->getParameters(), $actual->getParameters())
            ->assertEquals($expected->getParameters(), $actual->getParameters());

        $this->factory->getComparatorFor($expected->getInvalidValue(), $actual->getInvalidValue())
            ->assertEquals($expected->getInvalidValue(), $actual->getInvalidValue());

        if ($expected->getCause() !== null) {
            $this->factory->getComparatorFor($expected->getCause(), $actual->getCause())
                ->assertEquals($expected->getCause(), $actual->getCause());
        }

        // Should we also check the Root??
        if ($expected->getMessage() === $actual->getMessage() &&
            $expected->getMessageTemplate() === $actual->getMessageTemplate() &&
            $expected->getCode() === $actual->getCode() &&
            $expected->getPropertyPath() === $actual->getPropertyPath()
        ) {
            return;
        }

        throw new ComparisonFailure(
            $expected,
            $actual,
            $exportedExpected = $this->exporter->export($expected),
            $exportedActual = $this->exporter->export($actual),
            false,
            \sprintf(
                'Failed asserting that %s matches expected %s.',
                $exportedActual,
                $exportedExpected
            )
        );
    }
}
