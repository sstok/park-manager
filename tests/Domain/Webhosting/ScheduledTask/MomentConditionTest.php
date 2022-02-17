<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Webhosting\ScheduledTask;

use ParkManager\Domain\Webhosting\ScheduledTask\MomentCondition;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class MomentConditionTest extends TestCase
{
    /** @test */
    public function it_accepts_valid_formats(): void
    {
        self::assertSame('2022-02-10 12:00', (string) (new MomentCondition('2022-02-10 12:00')));
        self::assertSame('2020-02-29 12:00', (string) (new MomentCondition('2020-02-29 12:00')));
        self::assertSame('13:00', (string) (new MomentCondition('13:00')));
    }

    /**
     * @test
     * @dataProvider provideInvalidValues
     */
    public function it_rejects_invalid_values(string $value): void
    {
        $this->expectExceptionMessage('This value is not a valid moment.');

        new MomentCondition($value);
    }

    public function provideInvalidValues(): iterable
    {
        yield 'date only' => ['2022-02-10'];

        yield 'invalid date 1' => ['2022-02-10'];
        yield 'invalid date 2' => ['2022-2-10'];
        yield 'invalid date 3' => ['2022-02-29 12:00'];
        yield 'invalid date 4' => ['2022-13-10 12:00'];

        yield 'invalid time' => ['1:10'];
        yield 'invalid time 2' => ['10:1'];
        yield 'invalid time 3' => ['60:00'];

        yield 'invalid datetime' => ['2022-02-10 1:10'];
        yield 'invalid datetime 2' => ['2022-02-10 10:1'];
    }
}
