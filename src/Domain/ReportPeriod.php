<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain;

use Carbon\CarbonImmutable;
use ParkManager\Domain\Exception\PeriodAmountLessThanOne;
use ParkManager\Domain\Exception\PeriodEndNotGreaterThanStart;

final class ReportPeriod
{
    public CarbonImmutable $start;
    public CarbonImmutable $end;
    public PeriodUnit $unit;

    public function __construct(CarbonImmutable $start, CarbonImmutable $end, PeriodUnit $unit)
    {
        if ($end->lessThanOrEqualTo($start)) {
            throw new PeriodEndNotGreaterThanStart();
        }

        $this->assertCorrectAmountWithinRange($unit, $start, $end);

        $this->start = $start;
        $this->end = $end;
        $this->unit = $unit;
    }

    private function assertCorrectAmountWithinRange(PeriodUnit $unit, CarbonImmutable $start, CarbonImmutable $end): void
    {
        switch ($unit->value) {
            case 'hour':
                if ($start->diffInHours($end) < 1) {
                    throw new PeriodAmountLessThanOne('hour');
                }

                break;

            case 'day':
                if ($start->diffInDays($end) < 1) {
                    throw new PeriodAmountLessThanOne('day');
                }

                break;

            case 'week':
                if ($start->diffInWeeks($end) < 1) {
                    throw new PeriodAmountLessThanOne('week');
                }

                break;

            case 'month':
                if ($start->diffInMonths($end) < 1) {
                    throw new PeriodAmountLessThanOne('month');
                }

                break;

            case 'year':
                if ($start->diffInYears($end) < 1) {
                    throw new PeriodAmountLessThanOne('year');
                }

                break;
        }
    }
}
