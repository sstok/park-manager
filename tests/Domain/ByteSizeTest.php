<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Domain\ByteSize;
use ParkManager\Domain\Exception\InvalidByteSize;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class ByteSizeTest extends TestCase
{
    /** @test */
    public function constructable_with_various_units(): void
    {
        self::assertSame(1, (new ByteSize(1, 'b'))->value);
        self::assertSame(1, (new ByteSize(1, 'byte'))->value);

        // Ibi bytes
        self::assertSame(1024, (new ByteSize(1, 'kib'))->value);
        self::assertSame(1024 * 1024, (new ByteSize(1, 'mib'))->value);
        self::assertSame(1024 * 1024 * 1024, (new ByteSize(1, 'gib'))->value);

        self::assertSame(1024, (new ByteSize(1, 'ki'))->value);
        self::assertSame(1024 * 1024, (new ByteSize(1, 'mi'))->value);
        self::assertSame(1024 * 1024 * 1024, (new ByteSize(1, 'gi'))->value);

        // Normal bytes
        self::assertSame(1000, (new ByteSize(1, 'kb'))->value);
        self::assertSame(1000 * 1000, (new ByteSize(1, 'mb'))->value);
        self::assertSame(1000 * 1000 * 1000, (new ByteSize(1, 'gb'))->value);

        self::assertSame(1000, (new ByteSize(1, 'k'))->value);
        self::assertSame(1000 * 1000, (new ByteSize(1, 'm'))->value);
        self::assertSame(1000 * 1000 * 1000, (new ByteSize(1, 'g'))->value);

        // Different value
        self::assertSame(1024 * 5, (new ByteSize(5, 'kib'))->value);
        self::assertSame(1024 * 1024 * 5, (new ByteSize(5, 'mib'))->value);
        self::assertSame(1024 * 1024 * 1024 * 5, (new ByteSize(5, 'gib'))->value);
        self::assertSame(1024 * 5, (new ByteSize(5, 'ki'))->value);
        self::assertSame(1024 * 1024 * 5, (new ByteSize(5, 'mi'))->value);
        self::assertSame(1024 * 1024 * 1024 * 5, (new ByteSize(5, 'gi'))->value);
        self::assertSame(1000 * 5, (new ByteSize(5, 'kb'))->value);
        self::assertSame(1000 * 1000 * 5, (new ByteSize(5, 'mb'))->value);
        self::assertSame(1000 * 1000 * 1000 * 5, (new ByteSize(5, 'gb'))->value);
        self::assertSame(1000 * 5, (new ByteSize(5, 'k'))->value);
        self::assertSame(1000 * 1000 * 5, (new ByteSize(5, 'm'))->value);
        self::assertSame(1000 * 1000 * 1000 * 5, (new ByteSize(5, 'g'))->value);

        // Fractions
        self::assertSame((int) (1024 * 1.30), (new ByteSize(1.30, 'kib'))->value);
        self::assertSame((int) (1024 * 1024 * 1.30), (new ByteSize(1.30, 'mib'))->value);
        self::assertSame((int) (1024 * 1024 * 1024 * 1.30), (new ByteSize(1.30, 'gib'))->value);

        self::assertSame((int) (1000 * 1.6), (new ByteSize(1.60, 'k'))->value);
        self::assertSame((int) (1000 * 1000 * 1.6), (new ByteSize(1.60, 'm'))->value);
        self::assertSame((int) (1000 * 1000 * 1000 * 1.6), (new ByteSize(1.60, 'g'))->value);

        self::assertSame((int) (1024 * 1.30), (new ByteSize(1.30, 'kib'))->value);
        self::assertSame((int) (1024 * 1024 * 1.30), (new ByteSize(1.30, 'mib'))->value);
        self::assertSame((int) (1024 * 1024 * 1024 * 1.30), (new ByteSize(1.30, 'gib'))->value);

        self::assertSame((int) (1000 * 1.5), (new ByteSize(1.50, 'k'))->value);
        self::assertSame((int) (1000 * 1000 * 1.5), (new ByteSize(1.50, 'm'))->value);
        self::assertSame((int) (1000 * 1000 * 1000 * 1.5), (new ByteSize(1.50, 'g'))->value);

        self::assertSame((int) (1000 * 1.6), (new ByteSize(1.60, 'k'))->value);
        self::assertSame((int) (1000 * 1000 * 1.6), (new ByteSize(1.60, 'm'))->value);
        self::assertSame((int) (1000 * 1000 * 1000 * 1.6), (new ByteSize(1.60, 'g'))->value);
    }

    /** @test */
    public function fails_with_unsupported_unit(): void
    {
        $this->expectExceptionObject(new InvalidByteSize('Unknown or unsupported unit "Yib".'));

        new ByteSize(1, 'Yib');
    }

    /** @test */
    public function fails_with_fraction_for_byte_unit(): void
    {
        $this->expectExceptionObject(new InvalidByteSize('The unit "byte" must be a whole number without a fraction.'));

        new ByteSize(1.5, 'b');
    }

    /** @test */
    public function allows_inf_value(): void
    {
        self::assertEquals(ByteSize::inf(), ByteSize::inf());
        self::assertEquals(-1, ByteSize::inf()->value);
        self::assertEquals(-1, ByteSize::inf()->getNormSize());
        self::assertTrue(ByteSize::inf()->isInf());
    }

    /**
     * @test
     * @dataProvider provideFromStringExamples
     */
    public function constructable_from_string(ByteSize $expected, string $input): void
    {
        $byteSize = ByteSize::fromString($input);

        self::assertEquals($expected, $byteSize);

        if (mb_stripos($input, 'i') !== false) {
            self::assertEquals(
                $expected,
                ByteSize::fromString($byteSize->format()),
                sprintf('With input "%s" got "%s".', $input, $byteSize->format())
            );
        }
    }

    public function provideFromStringExamples(): iterable
    {
        // Inf
        yield 'Inf' => [ByteSize::inf(), 'Inf'];
        yield 'inf' => [ByteSize::inf(), 'inf'];
        yield 'Inf -1' => [ByteSize::inf(), '-1'];

        // Prime bytes
        yield '0 B' => [new ByteSize(0, 'b'), '0 B'];
        yield '0 b' => [new ByteSize(0, 'b'), '0 b'];
        yield '1 B' => [new ByteSize(1, 'b'), '1 B'];
        yield '1 b' => [new ByteSize(1, 'b'), '1 b'];
        yield '1b' => [new ByteSize(1, 'b'), '1 b'];

        // Ibi bytes (whole)
        yield '12 kib' => [new ByteSize(12, 'kib'), '12 kib'];
        yield '12 KiB' => [new ByteSize(12, 'kib'), '12 KiB'];
        yield '12 KIB' => [new ByteSize(12, 'kib'), '12 KIB'];
        yield '12KIB' => [new ByteSize(12, 'kib'), '12KIB'];
        yield '12 Ki' => [new ByteSize(12, 'kib'), '12 Ki'];

        yield '12 mib' => [new ByteSize(12, 'mib'), '12 mib'];
        yield '12 MiB' => [new ByteSize(12, 'mib'), '12 MiB'];
        yield '12 MIB' => [new ByteSize(12, 'mib'), '12 MIB'];

        yield '12 gib' => [new ByteSize(12, 'gib'), '12 gib'];
        yield '12 GiB' => [new ByteSize(12, 'gib'), '12 GiB'];
        yield '12 GIB' => [new ByteSize(12, 'gib'), '12 GIB'];

        // Normal bytes (whole)
        yield '12 kb' => [new ByteSize(12, 'kb'), '12 kb'];
        yield '12 Kb' => [new ByteSize(12, 'kb'), '12 Kb'];
        yield '12 KB' => [new ByteSize(12, 'kb'), '12 KB'];
        yield '12KB' => [new ByteSize(12, 'kb'), '12KB'];
        yield '12 K' => [new ByteSize(12, 'kb'), '12 K'];

        yield '12 mb' => [new ByteSize(12, 'mb'), '12 mb'];
        yield '12 Mb' => [new ByteSize(12, 'mb'), '12 Mb'];
        yield '12 MB' => [new ByteSize(12, 'mb'), '12 MB'];

        yield '12 gb' => [new ByteSize(12, 'gb'), '12 gb'];
        yield '12 Gb' => [new ByteSize(12, 'gb'), '12 Gb'];
        yield '12 GB' => [new ByteSize(12, 'gb'), '12 GB'];

        // Ibi bytes (with fraction)
        yield '12.00 kib' => [new ByteSize(12.00, 'kib'), '12.00 KiB'];
        yield '12.00 Mib' => [new ByteSize(12.00, 'mib'), '12.00 MiB'];
        yield '12.00 Gib' => [new ByteSize(12.00, 'gib'), '12.00 GiB'];
        yield '12.00Gib' => [new ByteSize(12.00, 'gib'), '12.00 GiB'];

        yield '12.50 kib' => [(new ByteSize(12.50, 'kib')), '12.50 KiB'];
        yield '12.50 Mib' => [(new ByteSize(12.50, 'mib')), '12.50 MiB'];
        yield '12.50 Gib' => [(new ByteSize(12.50, 'gib')), '12.50 GiB'];
        yield '12.30 kib' => [(new ByteSize(12.30, 'kib')), '12.30 KiB'];
        yield '12.30 Mib' => [(new ByteSize(12.30, 'mib')), '12.30 MiB'];
        yield '12.30 Gib' => [(new ByteSize(12.30, 'gib')), '12.30 GiB'];
        yield '12.60 Gib' => [(new ByteSize(12.60, 'gib')), '12.60 GiB'];
    }

    /**
     * @test
     * @dataProvider provideFromStringInvalidExamples
     */
    public function fails_from_string_with_invalid_input(string $input, $message): void
    {
        $this->expectExceptionObject(new InvalidByteSize($message));

        ByteSize::fromString($input);
    }

    public function provideFromStringInvalidExamples(): iterable
    {
        yield '-Inf' => ['-Inf', 'Invalid ByteSize format provided "-Inf". Expected value and unit as either "12 Mib" or "12 MB". Or "inf" otherwise.'];
        yield 'v 12' => ['12', 'Invalid ByteSize format provided "12". Expected value and unit as either "12 Mib" or "12 MB". Or "inf" otherwise.'];
        yield '12 Yib' => ['12 Yib', 'Unknown or unsupported unit "Yib".'];
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
        self::assertSame('200 B', (new ByteSize(200, 'b'))->format());
        self::assertSame('1.00 MiB', (new ByteSize(1, 'Mib'))->format());
        self::assertSame('1.00 KiB', (new ByteSize(1, 'Kib'))->format());
        self::assertSame('1.00 GiB', (new ByteSize(1, 'Gib'))->format());
        self::assertSame('200.00 GiB', (new ByteSize(200, 'Gib'))->format());

        self::assertSame('1.49 MiB', (new ByteSize(1, 'Mib'))->increase(new ByteSize(500, 'Kib'))->format());
        self::assertSame('5.00 MiB', (new ByteSize(1, 'Kib'))->increase(new ByteSize(5, 'Mib'))->format());
        self::assertSame('1.49 GiB', (new ByteSize(1, 'Gib'))->increase(new ByteSize(500, 'Mib'))->format());
    }

    /** @test */
    public function gets_unit(): void
    {
        self::assertSame('B', (new ByteSize(200, 'b'))->getUnit());
        self::assertSame('MiB', (new ByteSize(1, 'Mib'))->getUnit());
        self::assertSame('MiB', (new ByteSize(1025, 'Kib'))->getUnit());
        self::assertSame('KiB', (new ByteSize(1, 'Kib'))->getUnit());
        self::assertSame('GiB', (new ByteSize(1, 'Gib'))->getUnit());
    }

    /** @test */
    public function gets_norm_size(): void
    {
        self::assertSame(200, (new ByteSize(200, 'b'))->getNormSize());
        self::assertSame(1.0, (new ByteSize(1, 'Mib'))->getNormSize());
        self::assertSame(1.0, (new ByteSize(1025, 'Kib'))->getNormSize()); // 1.00 MiB
        self::assertSame(1.95, (new ByteSize(2000, 'Kib'))->getNormSize()); // 1.95 MiB
        self::assertSame(1.0, (new ByteSize(1, 'Kib'))->getNormSize());
        self::assertSame(1.0, (new ByteSize(1, 'Gib'))->getNormSize());
    }

    /** @test */
    public function gets_remaining_difference(): void
    {
        $this->assertDiffRemainderEquals(100, ByteSize::inf(), ByteSize::inf());
        $this->assertDiffRemainderEquals(100, new ByteSize(100, 'b'), ByteSize::inf());
        $this->assertDiffRemainderEquals(100, ByteSize::inf(), new ByteSize(100, 'b'));
        $this->assertDiffRemainderEquals(0, new ByteSize(100, 'b'), new ByteSize(100, 'b'));
        $this->assertDiffRemainderEquals(40, new ByteSize(100, 'b'), new ByteSize(60, 'b'));
        $this->assertDiffRemainderEquals(40, new ByteSize(100, 'b'), new ByteSize(60, 'b'));
        $this->assertDiffRemainderEquals(0, new ByteSize(100, 'b'), new ByteSize(100, 'b'));

        // With different unites. Note that some lose of precision is acceptable.
        $this->assertDiffRemainderEquals(100, new ByteSize(100, 'Gib'), new ByteSize(60, 'b'));
        $this->assertDiffRemainderEquals(51, new ByteSize(1, 'Gib'), new ByteSize(500, 'MiB'));
        $this->assertDiffRemainderEquals(10, new ByteSize(10, 'Gib'), new ByteSize(9, 'GiB'));
        $this->assertDiffRemainderEquals(1, new ByteSize(10, 'Gib'), new ByteSize(9.9, 'GiB'));
    }

    private function assertDiffRemainderEquals(int $expected, ByteSize $original, ByteSize $current): void
    {
        self::assertEqualsWithDelta($expected, round($original->getDiffRemainder($current), 0, \PHP_ROUND_HALF_UP), 0.0001);
    }

    /** @test */
    public function provides_debug_information(): void
    {
        self::assertSame(
            [
                'value' => 209715200,
                '_formatted' => '200.00 MiB',
            ],
            (new ByteSize(200, 'Mib'))->__debugInfo()
        );
    }
}
