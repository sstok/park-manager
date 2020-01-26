<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Tests\Domain\Mock\MockIdentity;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class UuidTraitTest extends TestCase
{
    /** @test */
    public function it_allows_creating_new_instance(): void
    {
        $id = MockIdentity::create();

        static::assertInstanceOf(MockIdentity::class, $id);
    }

    /** @test */
    public function it_allows_comparing(): void
    {
        $id = MockIdentity::create();
        $id2 = MockIdentity::create();

        static::assertTrue($id->equals($id));
        static::assertFalse($id->equals($id2));
        static::assertFalse($id->equals(false));

        $id = MockIdentity::fromString('56253090-3960-11e7-94fd-acbc32b58315');

        static::assertTrue($id->equals($id));
        static::assertFalse($id->equals($id2));
        static::assertFalse($id->equals(false));
    }

    /** @test */
    public function it_can_be_cast_to_string(): void
    {
        $id = MockIdentity::fromString('56253090-3960-11e7-94fd-acbc32b58315');

        static::assertEquals('56253090-3960-11e7-94fd-acbc32b58315', (string) $id);
    }

    /** @test */
    public function its_serializable(): void
    {
        $id = MockIdentity::fromString('56253090-3960-11e7-94fd-acbc32b58315');
        $serialized = \serialize($id);

        static::assertEquals($id, \unserialize($serialized, []));
    }

    /** @test */
    public function its_json_serializable(): void
    {
        $id = MockIdentity::fromString('56253090-3960-11e7-94fd-acbc32b58315');
        $serialized = \json_encode($id);

        static::assertEquals('56253090-3960-11e7-94fd-acbc32b58315', \json_decode($serialized, true));
    }
}
