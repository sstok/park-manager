<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\UI\Web\Form;

use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Comparator\Factory as ComparatorFactory;
use Symfony\Component\Form\FormError;

final class IsFormErrorsEqual extends Constraint
{
    /** @var array<array-key, FormError> */
    private array $value;

    /**
     * @param array<array-key, FormError>|FormError $expected
     */
    public function __construct(array | object $expected)
    {
        $this->value = $this->ensureArray($expected);
    }

    /**
     * @param array<array-key, FormError>|FormError $other
     *
     * @return array<array-key, FormError>
     */
    private function ensureArray(array | object $other): array
    {
        if (\is_object($other) && $other instanceof FormError) {
            $other = [$other];
        } elseif (! \is_array($other)) {
            $other = [...$other];
        }

        return $other;
    }

    /**
     * @param array<array-key, FormError>|FormError $other
     */
    public function evaluate($other, string $description = '', bool $returnResult = false): bool
    {
        $other = $this->ensureArray($other);

        $comparatorFactory = new ComparatorFactory();
        $comparatorFactory->register(new FormErrorComparator());

        try {
            $comparator = $comparatorFactory->getComparatorFor($this->value, $other);
            $comparator->assertEquals($this->value, $other);
        } catch (ComparisonFailure $f) {
            if ($returnResult) {
                return false;
            }

            throw new ExpectationFailedException(trim($description . "\n" . $f->getMessage()), $f);
        }

        return true;
    }

    public function toString(): string
    {
        return sprintf('is equal to %s', $this->exporter()->export($this->value));
    }
}
