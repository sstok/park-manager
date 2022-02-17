<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\ScheduledTask;

use ParkManager\Domain\Webhosting\ScheduledTask\CronCondition;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class CronConditionTest extends TestCase
{
    /** @test */
    public function it_accepts_valid_formats(): void
    {
        self::assertSame('@daily', (string) (new CronCondition('@daily')));
        self::assertSame('* * * * *', (string) (new CronCondition('* * * * *')));
    }

    /** @test */
    public function it_rejects_date_format(): void
    {
        $this->expectExceptionMessage('This value is not a valid CRON expression.');

        new CronCondition('2022-02-10 12:00');
    }

    /** @test */
    public function it_rejects_time_format(): void
    {
        $this->expectExceptionMessage('This value is not a valid CRON expression.');

        new CronCondition('12:00');
    }
}
