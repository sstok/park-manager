<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use Carbon\CarbonImmutable;
use Generator;
use ParkManager\Domain\Exception\PeriodAmountLessThanOne;
use ParkManager\Domain\Exception\PeriodEndNotGreaterThanStart;
use ParkManager\Domain\PeriodUnit;
use ParkManager\Domain\ReportPeriod;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ReportPeriodTest extends TestCase
{
    /** @test */
    public function it_checks_if_end_is_greater_then_start(): void
    {
        $start = new CarbonImmutable('2021-04-20 10:32:45');

        $this->expectExceptionObject(new PeriodEndNotGreaterThanStart());

        new ReportPeriod($start, $start, PeriodUnit::MONTH);
    }

    /**
     * @test
     * @dataProvider provideLesserThanUnitTests
     */
    public function it_checks_if_period_provides_enough_amount_for_unit(PeriodUnit $unit, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $this->expectExceptionObject(new PeriodAmountLessThanOne($unit->name));

        new ReportPeriod($start, $end, $unit);
    }

    /**
     * @return Generator<string, array{0: string, 1: CarbonImmutable, 2: CarbonImmutable}>
     */
    public function provideLesserThanUnitTests(): Generator
    {
        yield 'hour' => [PeriodUnit::HOUR, new CarbonImmutable('2021-04-20 10:32:45'), new CarbonImmutable('2021-04-20 10:40:00')];
        yield 'day' => [PeriodUnit::DAY, new CarbonImmutable('2021-04-20 10:32:45'), new CarbonImmutable('2021-04-20 15:40:00')];
        yield 'week' => [PeriodUnit::WEEK, new CarbonImmutable('2021-04-20 10:32:45'), new CarbonImmutable('2021-04-25 15:40:00')];
        yield 'month' => [PeriodUnit::MONTH, new CarbonImmutable('2021-04-20 10:32:45'), new CarbonImmutable('2021-05-10 10:40:00')];
        yield 'year' => [PeriodUnit::YEAR, new CarbonImmutable('2021-04-20 10:32:45'), new CarbonImmutable('2021-05-10 10:40:00')];
    }

    /**
     * @test
     * @dataProvider provideEnoughUnitAmount
     */
    public function it_accepts_periods_with_enough_amount_for_unit(PeriodUnit $unit, CarbonImmutable $start, CarbonImmutable $end): void
    {
        $period = new ReportPeriod($start, $end, $unit);

        self::assertSame($start, $period->start);
        self::assertSame($end, $period->end);
    }

    /**
     * @return Generator<string, array{0: string, 1: CarbonImmutable, 2: CarbonImmutable}>
     */
    public function provideEnoughUnitAmount(): Generator
    {
        yield 'hour' => [PeriodUnit::HOUR, new CarbonImmutable('2021-04-20 10:32:45'), new CarbonImmutable('2021-04-20 11:33:00')];
        yield 'day' => [PeriodUnit::DAY, new CarbonImmutable('2021-04-20 10:32:00'), new CarbonImmutable('2021-04-21 15:32:00')];
        yield 'week' => [PeriodUnit::WEEK, new CarbonImmutable('2021-04-20 10:32:00'), new CarbonImmutable('2021-04-27 10:32:00')];
        yield 'month' => [PeriodUnit::MONTH, new CarbonImmutable('2021-04-10 10:32:00'), new CarbonImmutable('2021-05-10 10:32:00')];
        yield 'year' => [PeriodUnit::YEAR, new CarbonImmutable('2021-05-20 10:32:00'), new CarbonImmutable('2022-05-20 10:32:00')];
    }
}
