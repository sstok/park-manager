<?php

/** @noinspection PhpConstantNamingConventionInspection */

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Domain\EnumEqualityTrait;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class EnumEqualityTraitTest extends TestCase
{
    /** @test */
    public function it_can_compare_to_other_instances(): void
    {
        self::assertFalse(EnumStub::equalsTo(EnumStub::FOO, EnumStub::Bar));
        self::assertFalse(EnumStub::equalsTo(EnumStub::FOO, null));
        self::assertFalse(EnumStub::equalsTo(null, EnumStub::FOO));
        self::assertFalse(EnumStub::equalsTo(null, null));

        self::assertFalse(EnumStub::equalsToAny(EnumStub::FOO, EnumStub::Bar));
        self::assertFalse(EnumStub::equalsToAny(EnumStub::FOO, null));
        self::assertFalse(EnumStub::equalsToAny(null, null));
        self::assertFalse(EnumStub::equalsToAny(null));

        self::assertTrue(EnumStub::equalsTo(EnumStub::FOO, EnumStub::FOO));
        self::assertTrue(EnumStub::equalsToAny(EnumStub::FOO, EnumStub::FOO));
        self::assertTrue(EnumStub::equalsToAny(EnumStub::FOO, EnumStub::Bar, EnumStub::FOO));
        self::assertTrue(EnumStub::equalsToAny(EnumStub::FOO, EnumStub::FOO, null));
        self::assertTrue(EnumStub::equalsToAny(EnumStub::FOO, null, EnumStub::FOO));
    }
}

/**
 * @internal
 */
enum EnumStub
{
    use EnumEqualityTrait;

    case FOO;

    case Bar;
}
