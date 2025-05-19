<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Infrastructure\Validator\Constraints;

use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;
use SebastianBergmann\Exporter\Exporter;
use Symfony\Component\Validator\ConstraintViolation;

trait ConstraintViolationComparatorTrait
{
    private static ?ConstraintViolationComparator $violationComparator = null;

    #[BeforeClass]
    public static function setUpValidatorComparator(): void
    {
        self::$violationComparator = new ConstraintViolationComparator();

        $comparatorFactory = ComparatorFactory::getInstance();
        $comparatorFactory->register(self::$violationComparator);
    }

    #[AfterClass]
    public static function tearDownValidatorComparator(): void
    {
        if (self::$violationComparator === null) {
            return;
        }

        $comparatorFactory = ComparatorFactory::getInstance();
        $comparatorFactory->unregister(self::$violationComparator);
        self::$violationComparator = null;
    }
}

final class ConstraintViolationComparator extends Comparator
{
    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof ConstraintViolation && $actual instanceof ConstraintViolation;
    }

    /**
     * @param ConstraintViolation $expected
     * @param ConstraintViolation $actual
     */
    public function assertEquals(mixed $expected, mixed $actual, float $delta = 0.0, bool $canonicalize = false, bool $ignoreCase = false): void
    {
        // Should we also check the Root??
        if ($this->equalsViolation($expected, $actual)
            && $expected->getMessage() === $actual->getMessage()
            && $expected->getMessageTemplate() === $actual->getMessageTemplate()
            && $expected->getCode() === $actual->getCode()
            && $expected->getPropertyPath() === $actual->getPropertyPath()
        ) {
            return;
        }

        $exporter = new Exporter();

        throw new ComparisonFailure(
            $expected,
            $actual,
            $exportedExpected = $exporter->export($expected),
            $exportedActual = $exporter->export($actual),
            \sprintf(
                'Failed asserting that %s matches expected %s.',
                $exportedActual,
                $exportedExpected
            )
        );
    }

    private function equalsViolation(ConstraintViolation $expected, ConstraintViolation $actual): bool
    {
        $factory = $this->factory();

        try {
            $factory->getComparatorFor($expected->getParameters(), $actual->getParameters())
                ->assertEquals($expected->getParameters(), $actual->getParameters());

            $factory->getComparatorFor($expected->getInvalidValue(), $actual->getInvalidValue())
                ->assertEquals($expected->getInvalidValue(), $actual->getInvalidValue());

            if ($expected->getCause() !== null) {
                $factory->getComparatorFor($expected->getCause(), $actual->getCause())
                    ->assertEquals($expected->getCause(), $actual->getCause());
            }

            return true;
        } catch (ComparisonFailure) {
            return false;
        }
    }
}
