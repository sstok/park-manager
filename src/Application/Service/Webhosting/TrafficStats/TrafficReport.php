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
use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\PeriodUnit;

final class TrafficReport
{
    /**
     * @var ResultSet<TrafficRow>
     */
    public ResultSet $rows;

    public CarbonImmutable $periodStart;
    public CarbonImmutable $periodEnd;
    public PeriodUnit $unit;
    public TrafficType $types;

    private ?ByteSize $totalUsage;

    public function getTotalUsage(): ByteSize
    {
        if ($this->totalUsage) {
            return $this->totalUsage;
        }

        $totalUsage = new ByteSize(0, 'b');

        /** @var TrafficRow $row */
        foreach ($this->rows as $row) {
            $totalUsage->increase($row->usage);
        }

        $this->totalUsage = $totalUsage;

        return $totalUsage;
    }
}
