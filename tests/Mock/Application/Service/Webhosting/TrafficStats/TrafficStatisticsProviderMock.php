<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Application\Service\Webhosting\TrafficStats;

use Assert\Assertion;
use Carbon\CarbonImmutable;
use Lifthill\Component\Common\Domain\Model\ByteSize;
use ParkManager\Application\Service\Webhosting\TrafficStats\TrafficReport;
use ParkManager\Application\Service\Webhosting\TrafficStats\TrafficStatisticsProvider;
use ParkManager\Application\Service\Webhosting\TrafficStats\TrafficType;
use ParkManager\Domain\PeriodUnit;
use ParkManager\Domain\ReportPeriod;

/**
 * @internal
 */
final class TrafficStatisticsProviderMock implements TrafficStatisticsProvider
{
    /**
     * @var array<TrafficReport>
     */
    private array $reports;

    /**
     * Reports should be provided by their exactly expected periods, unit and types.
     *
     * Note that a report that is limited to FTP only will not be included for ALL.
     *
     * For a monthly report the report:
     *   - The start/end period are expected at 01-xx-xxxx 00:00:00 and "last day of the month"-xx-xxxx 23:59:59
     *   - With PeriodUnit::Month
     *   - And TrafficType::ALL
     *
     * @param array<TrafficReport> $reports
     */
    public function __construct(array $reports = [])
    {
        Assertion::allIsInstanceOf($reports, TrafficReport::class);

        $this->reports = $reports;
    }

    public function getMonthlyTotal(int $month, int $year): ByteSize
    {
        Assertion::between($month, 1, 12, 'Month must be between ]1-12[');

        $start = CarbonImmutable::create($year, $month)->startOfDay();
        $end = $start->endOfMonth()->endOfDay();

        return $this->getFromPeriod(
            new ReportPeriod($start, $end, PeriodUnit::MONTH),
            new TrafficType('ALL')
        )->getTotalUsage();
    }

    public function getFromPeriod(ReportPeriod $period, ?TrafficType $types = null): ?TrafficReport
    {
        $types ??= new TrafficType(TrafficType::ALL);

        foreach ($this->reports as $report) {
            if ($report->periodStart->eq($period->start)
                && $report->periodEnd->eq($period->end)
                && $report->unit === $period->unit
                && $report->types->get() === $types->get()
            ) {
                return $report;
            }
        }

        return null;
    }
}
