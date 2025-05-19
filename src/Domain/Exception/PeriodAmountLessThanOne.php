<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Exception;

final class PeriodAmountLessThanOne extends InvalidArgument
{
    public function __construct(public string $unit)
    {
        parent::__construct(\sprintf('A TrafficPeriod with unit "%s" must contain at least one %s.', ucfirst(mb_strtolower($unit)), mb_strtolower($unit)));
    }

    public function getPublicMessage(): string
    {
        return \sprintf('A TrafficPeriod with unit "{unit}" must contain at least one %s.', mb_strtolower($this->unit));
    }
}
