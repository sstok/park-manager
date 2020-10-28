<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Domain\ByteSize;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ByteSizeTest extends TestCase
{
    /** @test */
    public function constructable_with_various_units(): void
    {
        self::assertEquals(1, (new ByteSize(1, 'b'))->value);
        self::assertEquals(1, (new ByteSize(1, 'byte'))->value);

        // Ibi bytes
        self::assertEquals(1024, (new ByteSize(1, 'kib'))->value);
        self::assertEquals(1024 * 1024, (new ByteSize(1, 'mib'))->value);
        self::assertEquals(1024 * 1024 * 1024, (new ByteSize(1, 'gib'))->value);

        self::assertEquals(1024, (new ByteSize(1, 'ki'))->value);
        self::assertEquals(1024 * 1024, (new ByteSize(1, 'mi'))->value);
        self::assertEquals(1024 * 1024 * 1024, (new ByteSize(1, 'gi'))->value);

        // Normal bytes
        self::assertEquals(1000, (new ByteSize(1, 'kb'))->value);
        self::assertEquals(1000 * 1000, (new ByteSize(1, 'mb'))->value);
        self::assertEquals(1000 * 1000 * 1000, (new ByteSize(1, 'gb'))->value);

        self::assertEquals(1000, (new ByteSize(1, 'k'))->value);
        self::assertEquals(1000 * 1000, (new ByteSize(1, 'm'))->value);
        self::assertEquals(1000 * 1000 * 1000, (new ByteSize(1, 'g'))->value);

        // Different value
        self::assertEquals(1024 * 5, (new ByteSize(5, 'kib'))->value);
        self::assertEquals(1024 * 1024 * 5, (new ByteSize(5, 'mib'))->value);
        self::assertEquals(1024 * 1024 * 1024 * 5, (new ByteSize(5, 'gib'))->value);
        self::assertEquals(1024 * 5, (new ByteSize(5, 'ki'))->value);
        self::assertEquals(1024 * 1024 * 5, (new ByteSize(5, 'mi'))->value);
        self::assertEquals(1024 * 1024 * 1024 * 5, (new ByteSize(5, 'gi'))->value);
        self::assertEquals(1000 * 5, (new ByteSize(5, 'kb'))->value);
        self::assertEquals(1000 * 1000 * 5, (new ByteSize(5, 'mb'))->value);
        self::assertEquals(1000 * 1000 * 1000 * 5, (new ByteSize(5, 'gb'))->value);
        self::assertEquals(1000 * 5, (new ByteSize(5, 'k'))->value);
        self::assertEquals(1000 * 1000 * 5, (new ByteSize(5, 'm'))->value);
        self::assertEquals(1000 * 1000 * 1000 * 5, (new ByteSize(5, 'g'))->value);
    }

    /** @test */
    public function fails_with_unsupported_unit(): void
    {
        $this->expectExceptionObject(new \InvalidArgumentException('Unknown or unsupported unit "Yib".'));

        new ByteSize(1, 'Yib');
    }

    /** @test */
    public function allows_inf_value(): void
    {
        self::assertEquals(ByteSize::inf(), ByteSize::inf());
        self::assertEquals(-1, ByteSize::inf()->value);
        self::assertTrue(ByteSize::inf()->isInf());
    }

    /** @test */
    public function comparable(): void
    {
        // Equals
        self::assertTrue((new ByteSize(1, 'kib'))->equals(new ByteSize(1, 'kib')));
        self::assertTrue(ByteSize::inf()->equals(ByteSize::inf()));

        self::assertFalse((new ByteSize(2, 'kib'))->equals(new ByteSize(1, 'kib')));
        self::assertFalse((new ByteSize(2, 'kib'))->equals(new ByteSize(2, 'Gib')));
        self::assertFalse(ByteSize::inf()->equals(new ByteSize(1, 'kib')));
        self::assertFalse(ByteSize::inf()->equals(new ByteSize(1, 'kib')));
        self::assertFalse((new ByteSize(2, 'kib'))->equals(null));

        // Less than
        self::assertTrue((new ByteSize(1, 'Mib'))->lessThan((new ByteSize(2, 'Mib'))));
        self::assertTrue((new ByteSize(1, 'Mib'))->lessThan((new ByteSize(2, 'Gib'))));
        self::assertTrue((new ByteSize(1, 'Gib'))->lessThan((new ByteSize(2, 'Gib'))));
        self::assertTrue((new ByteSize(1, 'Gib'))->lessThan(ByteSize::inf()));

        self::assertTrue((new ByteSize(1, 'Mib'))->lessThanOrEqualTo((new ByteSize(2, 'Mib'))));
        self::assertTrue((new ByteSize(1, 'Mib'))->lessThanOrEqualTo((new ByteSize(2, 'Gib'))));
        self::assertTrue((new ByteSize(1, 'Gib'))->lessThanOrEqualTo((new ByteSize(2, 'Gib'))));
        self::assertTrue((new ByteSize(1, 'Gib'))->lessThanOrEqualTo(ByteSize::inf()));

        self::assertTrue((new ByteSize(1, 'Mib'))->lessThanOrEqualTo((new ByteSize(1, 'Mib'))));
        self::assertTrue(ByteSize::inf()->lessThanOrEqualTo(ByteSize::inf()));

        self::assertFalse((new ByteSize(1, 'Gib'))->lessThan((new ByteSize(2, 'Mib'))));
        self::assertFalse((new ByteSize(1, 'Gib'))->lessThan((new ByteSize(1, 'Gib'))));
        self::assertFalse((new ByteSize(1, 'Gib'))->lessThanOrEqualTo((new ByteSize(2, 'Mib'))));
        self::assertFalse(ByteSize::inf()->lessThan((new ByteSize(2, 'Gib'))));
        self::assertFalse(ByteSize::inf()->lessThanOrEqualTo((new ByteSize(2, 'Gib'))));

        // Greater than
        self::assertTrue((new ByteSize(2, 'Mib'))->greaterThan((new ByteSize(1, 'Mib'))));
        self::assertTrue((new ByteSize(1, 'Gib'))->greaterThan((new ByteSize(1, 'Mib'))));
        self::assertTrue(ByteSize::inf()->greaterThan((new ByteSize(1, 'Mib'))));

        self::assertTrue((new ByteSize(2, 'Mib'))->greaterThanOrEqualTo((new ByteSize(1, 'Mib'))));
        self::assertTrue((new ByteSize(1, 'Gib'))->greaterThanOrEqualTo((new ByteSize(1, 'Mib'))));
        self::assertTrue((new ByteSize(2, 'Mib'))->greaterThanOrEqualTo((new ByteSize(2, 'Mib'))));
        self::assertTrue(ByteSize::inf()->greaterThanOrEqualTo((new ByteSize(1, 'Mib'))));
        self::assertTrue(ByteSize::inf()->greaterThanOrEqualTo(ByteSize::inf()));

        self::assertFalse((new ByteSize(1, 'Mib'))->greaterThan((new ByteSize(2, 'Mib'))));
        self::assertFalse((new ByteSize(1, 'Mib'))->greaterThan((new ByteSize(1, 'Mib'))));
        self::assertFalse((new ByteSize(1, 'Mib'))->greaterThan((new ByteSize(1, 'Gib'))));
        self::assertFalse((new ByteSize(1, 'Mib'))->greaterThan(ByteSize::inf()));

        self::assertFalse((new ByteSize(2, 'Mib'))->greaterThanOrEqualTo((new ByteSize(1, 'Gib'))));
        self::assertFalse((new ByteSize(1, 'Mib'))->greaterThanOrEqualTo((new ByteSize(1, 'Gib'))));
        self::assertFalse((new ByteSize(1, 'Mib'))->greaterThanOrEqualTo(ByteSize::inf()));
    }

    /** @test */
    public function allows_increasing(): void
    {
        $value = new ByteSize(1, 'Mib');
        $increased = $value->increase(new ByteSize(1, 'Mib'));

        self::assertNotSame($value, $increased, 'Original value must not be mutated');
        self::assertEquals(new ByteSize(2, 'Mib'), $increased);
        self::assertEquals(new ByteSize((1024 * 1024) + (1024 * 1024), 'b'), $value->increase(new ByteSize(1, 'Mib')));
        self::assertEquals(new ByteSize((1024 * 1024) + (1000 * 1000 * 1000), 'b'), $value->increase(new ByteSize(1, 'Gb')));

        self::assertEquals(ByteSize::inf(), $value->increase(ByteSize::inf()));
        self::assertEquals(ByteSize::inf(), ByteSize::inf()->increase(ByteSize::inf()));
        self::assertEquals(ByteSize::inf(), ByteSize::inf()->increase($value));
    }

    /** @test */
    public function allows_decreasing(): void
    {
        $value = new ByteSize(10, 'Mib');
        $decreased = $value->decrease(new ByteSize(1, 'Mib'));

        self::assertNotSame($value, $decreased, 'Original value must not be mutated');
        self::assertEquals(new ByteSize(9, 'Mib'), $decreased);
        self::assertEquals(new ByteSize(0, 'b'), $value->decrease(new ByteSize(1, 'Gib')));
        self::assertEquals(new ByteSize(0, 'b'), $value->decrease($value));
        self::assertEquals(new ByteSize(-1063256064, 'b'), $value->decrease(new ByteSize(1, 'Gib'), false));

        self::assertEquals($value, $value->decrease(ByteSize::inf()), 'Expect lowest value is used, when not inf');
        self::assertEquals($value, ByteSize::inf()->decrease($value), 'Expect lowest value is used, when not inf');
        self::assertEquals(ByteSize::inf(), ByteSize::inf()->decrease(ByteSize::inf()));
    }

    /** @test */
    public function formats(): void
    {
        self::assertEquals('200 B', (new ByteSize(200, 'b'))->format());
        self::assertEquals('1.00 MiB', (new ByteSize(1, 'Mib'))->format());
        self::assertEquals('1.00 KiB', (new ByteSize(1, 'Kib'))->format());
        self::assertEquals('1.00 GiB', (new ByteSize(1, 'Gib'))->format());
        self::assertEquals('200.00 GiB', (new ByteSize(200, 'Gib'))->format());

        self::assertEquals('1.49 MiB', (new ByteSize(1, 'Mib'))->increase(new ByteSize(500, 'Kib'))->format());
        self::assertEquals('5.00 MiB', (new ByteSize(1, 'Kib'))->increase(new ByteSize(5, 'Mib'))->format());
        self::assertEquals('1.49 GiB', (new ByteSize(1, 'Gib'))->increase(new ByteSize(500, 'Mib'))->format());
    }

    /** @test */
    public function gets_unit(): void
    {
        self::assertEquals('B', (new ByteSize(200, 'b'))->getUnit());
        self::assertEquals('MiB', (new ByteSize(1, 'Mib'))->getUnit());
        self::assertEquals('MiB', (new ByteSize(1025, 'Kib'))->getUnit());
        self::assertEquals('KiB', (new ByteSize(1, 'Kib'))->getUnit());
        self::assertEquals('GiB', (new ByteSize(1, 'Gib'))->getUnit());
    }

    /** @test */
    public function provides_debug_information(): void
    {
        self::assertEquals(
            [
                'value' => 209715200,
                '_formatted' => '200.00 MiB',
            ],
            (new ByteSize(200, 'Mib'))->__debugInfo()
        );
    }
}
