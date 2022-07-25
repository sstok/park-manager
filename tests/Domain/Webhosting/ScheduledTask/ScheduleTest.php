<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\ScheduledTask;

use ParkManager\Domain\Webhosting\ScheduledTask\CronCondition;
use ParkManager\Domain\Webhosting\ScheduledTask\MomentCondition;
use ParkManager\Domain\Webhosting\ScheduledTask\Schedule;
use ParkManager\Domain\Webhosting\ScheduledTask\ScheduleType;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ScheduleTest extends TestCase
{
    /** @test */
    public function it_sets_proper_type(): void
    {
        $schedule = new Schedule(new CronCondition('@daily'));
        self::assertSame(ScheduleType::CRON, $schedule->type);

        $schedule = new Schedule(new MomentCondition('12:24'));
        self::assertSame(ScheduleType::MOMENT, $schedule->type);
    }

    /** @test */
    public function its_comparable(): void
    {
        $schedule = new Schedule(new CronCondition('@daily'));

        self::assertTrue($schedule->equals($schedule));
        self::assertTrue($schedule->equals(new Schedule(new CronCondition('@daily'))));

        $schedule2 = new Schedule(new MomentCondition('12:24'));
        self::assertTrue($schedule2->equals($schedule2));
        self::assertTrue($schedule2->equals(new Schedule(new MomentCondition('12:24'))));

        self::assertFalse($schedule->equals($schedule2));
        self::assertFalse($schedule2->equals($schedule));
    }
}
