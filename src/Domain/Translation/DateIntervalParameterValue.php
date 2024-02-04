<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Translation;

use Carbon\CarbonInterval;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DateIntervalParameterValue implements ParameterValue
{
    private CarbonInterval $value;

    /**
     * @param array<string, mixed>|string|null $syntax
     *
     * @see \Carbon\CarbonInterval::forHumans for configurations
     */
    public function __construct(
        \DateInterval $value,
        private null | array | string $syntax = null,
    ) {
        if (! $value instanceof CarbonInterval) {
            $value = CarbonInterval::instance($value);
        } else {
            $value = clone $value;
        }

        $this->value = $value;
    }

    public function format(string $locale, callable $escaper, TranslatorInterface $translator): string
    {
        return $escaper($this->value->locale($locale)->forHumans($this->syntax));
    }
}
