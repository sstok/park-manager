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
use ParkManager\Domain\Webhosting\ScheduledTask\Task;
use ParkManager\Domain\Webhosting\ScheduledTask\TaskId;
use ParkManager\Tests\Mock\Domain\Webhosting\SpaceRepositoryMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class TaskTest extends TestCase
{
    /** @test */
    public function it_only_changes_schedule_when_actually_different(): void
    {
        $schedule1 = new Schedule(new CronCondition('@daily'));
        $schedule2 = new Schedule(new MomentCondition('12:24'));

        $task = new Task(
            TaskId::fromString('017f074c-37d3-1e3e-0165-30c16d573168'),
            SpaceRepositoryMock::createSpace(),
            $schedule1,
            'bin/cron-wake-up.php'
        );

        $task->setSchedule($schedule1);
        self::assertSame($schedule1, $task->schedule);

        $task->setSchedule(new Schedule(new CronCondition('@daily')));
        self::assertSame($schedule1, $task->schedule);

        $task->setSchedule($schedule2);
        self::assertSame($schedule2, $task->schedule);
    }

    /** @test */
    public function it_allows_disabling(): void
    {
        $task = new Task(
            TaskId::fromString('017f074c-37d3-1e3e-0165-30c16d573168'),
            SpaceRepositoryMock::createSpace(),
            new Schedule(new CronCondition('@daily')),
            'bin/cron-wake-up.php'
        );

        $task->disable();
        self::assertFalse($task->enabled);

        $task->enable();
        self::assertTrue($task->enabled);
    }
}
