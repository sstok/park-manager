<?php

/** @noinspection PhpConstantNamingConventionInspection */

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Domain\EnumTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EnumTraitTest extends TestCase
{
    /** @test */
    public function it_rejects_duplicate_names(): void
    {
        $this->expectExceptionMessage('Cannot redeclare ' . DuplicateEnumCasesStub::class . '::FOO');

        DuplicateEnumCasesStub::get('FOO');
    }

    /** @test */
    public function it_rejects_duplicate_values(): void
    {
        $this->expectExceptionMessage('Duplicate value in enum ' . DuplicateEnumValuesStub::class . ' for cases FOO and BAR');

        DuplicateEnumValuesStub::get('FOO');
    }

    /** @test */
    public function it_rejects_differing_types_int(): void
    {
        $this->expectExceptionMessage('Enum case type int does not match enum scalar type string');

        EnumWithStringAndIntTypeStub::get('FOO');
    }

    /** @test */
    public function it_rejects_differing_types_string(): void
    {
        $this->expectExceptionMessage('Enum case type string does not match enum scalar type int');

        EnumWithIntAndStringTypeStub::get('FOO');
    }

    /** @test */
    public function it_works_the_same_native(): void
    {
        // Custom method for creating
        self::assertSame(IntEnumStub::get('FOO'), IntEnumStub::get('FOO'));
        self::assertSame(IntEnumStub::get('FOO'), IntEnumStub::get('Foo'));
        self::assertSame(StringEnumStub::get('FOO'), StringEnumStub::get('Foo'));

        // Custom method for comparing
        self::assertTrue(StringEnumStub::get('FOO')->equals(StringEnumStub::get('Foo')));
        self::assertFalse(StringEnumStub::get('FOO')->equals(StringEnumStub::get('BAR')));

        // Cases
        self::assertSame([StringEnumStub::get('FOO'), StringEnumStub::get('Bar')], StringEnumStub::cases());

        // tryFrom
        self::assertNull(StringEnumStub::tryFrom('beep'));

        // From
        self::assertSame(StringEnumStub::get('FOO'), StringEnumStub::from(StringEnumStub::FOO));
        self::assertSame(StringEnumStub::get('FOO'), StringEnumStub::tryFrom(StringEnumStub::FOO));

        $this->expectExceptionMessage('Unable to find matching case for value "nope"');

        StringEnumStub::from('nope');
    }

    /** @test */
    public function it_errors_for_unknown_case(): void
    {
        $this->expectExceptionMessage('Enum case Who is not defined');

        StringEnumStub::get('Who');
    }

    /**
     * @test
     */
    public function it_errors_with_from_with_different_type_string(): void
    {
        $this->expectExceptionMessage(IntEnumStub::class . '::from(): Argument #1 ($value) must be of type int, string given');

        IntEnumStub::from('Bar');
    }

    /**
     * @test
     */
    public function it_errors_with_from_with_different_type_int(): void
    {
        $this->expectExceptionMessage(StringEnumStub::class . '::from(): Argument #1 ($value) must be of type string, int given');

        StringEnumStub::from(1);
    }
}

/**
 * @internal
 */
final class DuplicateEnumCasesStub
{
    public const FOO = 1;
    public const Foo = 1;

    use EnumTrait;
}

/**
 * @internal
 */
final class DuplicateEnumValuesStub
{
    public const FOO = 1;
    public const BAR = 1;
    public const WHO = 1;

    use EnumTrait;
}

/**
 * @internal
 */
final class EnumWithStringAndIntTypeStub
{
    public const FOO = '1';
    public const BAR = 2;

    use EnumTrait;
}

/**
 * @internal
 */
final class EnumWithIntAndStringTypeStub
{
    public const FOO = 1;
    public const BAR = '2';

    use EnumTrait;
}

/**
 * @internal
 */
final class IntEnumStub
{
    public const FOO = 1;
    public const Bar = 2;

    use EnumTrait;
}

/**
 * @internal
 */
final class StringEnumStub
{
    public const FOO = 'he';
    public const Bar = 'now';

    use EnumTrait;
}
