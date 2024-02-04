<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\Webhosting\TrafficStats;

use Carbon\CarbonImmutable;
use Lifthill\Component\Common\Domain\Model\ByteSize;

final class TrafficRow
{
    public ByteSize $usage;

    /**
     * Recorded moment of this statistic.
     *
     * Note that depending the PeriodUnit this might be normalized to
     * the first day of a month or year.
     */
    public CarbonImmutable $moment;
}
