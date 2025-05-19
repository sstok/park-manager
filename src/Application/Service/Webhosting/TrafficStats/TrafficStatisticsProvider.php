<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Service\Webhosting\TrafficStats;

use Lifthill\Component\Common\Domain\Model\ByteSize;
use ParkManager\Domain\ReportPeriod;

interface TrafficStatisticsProvider
{
    public function getMonthlyTotal(int $month, int $year): ByteSize;

    public function getFromPeriod(
        ReportPeriod $period,
        ?TrafficType $types = null
    ): ?TrafficReport;
}
