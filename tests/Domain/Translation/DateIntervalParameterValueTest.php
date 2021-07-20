<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain\Translation;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonInterval;
use DateInterval;
use ParkManager\Domain\Translation\DateIntervalParameterValue;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class DateIntervalParameterValueTest extends TestCase
{
    private \Closure $escaper;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->escaper = static fn (string $value): string => sprintf('{%s}', $value);

        CarbonInterval::setLocale('en');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(false);
    }

    /** @test */
    public function it_formats_date_interval_period(): void
    {
        $invertedIntervalPeriod = new DateInterval('P1W2D');
        $invertedIntervalPeriod->invert = 1;

        Carbon::setTestNow('2021-07-22T11:01:59 CET');

        $carbonInterval = CarbonInterval::instance(new DateInterval('P1W2D'));

        $this->assertFormattedEquals('{1 day 12 hours}', new DateIntervalParameterValue(new DateInterval('P1DT12H')));
        $this->assertFormattedEquals('{1 week 2 days}', new DateIntervalParameterValue($invertedIntervalPeriod));
        $this->assertFormattedEquals('{1 week 2 days}', new DateIntervalParameterValue($carbonInterval));
        $this->assertFormattedEquals('{1 week 2 days}', new DateIntervalParameterValue($carbonInterval));
        $this->assertFormattedEquals('{1 week 2 dagen}', new DateIntervalParameterValue($carbonInterval), 'nl');
    }

    /** @test */
    public function it_formats_date_interval_relative_ago(): void
    {
        $invertedInterval = new DateInterval('P1DT12H');
        $invertedInterval->invert = 1;

        Carbon::setTestNow('2021-07-22T11:01:59 CET');

        $carbonInterval = CarbonInterval::instance(new DateInterval('P1DT12H'));

        $this->assertFormattedEquals('{1 day 12 hours ago}', new DateIntervalParameterValue($carbonInterval, ['syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW]));
        $this->assertFormattedEquals('{1 day 12 hours from now}', new DateIntervalParameterValue($carbonInterval->invert(), ['syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW]));
        $this->assertFormattedEquals('{1 day 12 hours from now}', new DateIntervalParameterValue($invertedInterval, ['syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW]));
    }

    /** @test */
    public function it_does_not_change_original_instance(): void
    {
        $carbonInterval = CarbonInterval::instance(new DateInterval('P1DT12H'));

        self::assertSame('en', $carbonInterval->locale());

        $this->assertFormattedEquals('{1 dag 12 uur}', new DateIntervalParameterValue($carbonInterval), 'nl');

        self::assertSame('en', $carbonInterval->locale());
    }

    private function assertFormattedEquals(string $expected, DateIntervalParameterValue $value, string $locale = 'en'): void
    {
        self::assertSame($expected, $value->format($locale, $this->escaper, $this->translator));
    }
}
