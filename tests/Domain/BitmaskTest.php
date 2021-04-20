<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Domain;

use ParkManager\Domain\Bitmask;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class BitmaskTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        self::assertEquals(0, (new BitmaskStub())->get());
        self::assertEquals(123456, (new BitmaskStub(123456))->get());
    }

    /** @test */
    public function it_resolves_a_mask(): void
    {
        $bitmask = new BitmaskStub();

        self::assertEquals(BitmaskStub::VIEW, $bitmask->resolveMask('view'));
        self::assertEquals(BitmaskStub::VIEW, $bitmask->resolveMask(BitmaskStub::VIEW));
    }

    /** @test */
    public function it_adds_and_removes(): void
    {
        $bitmask = new BitmaskStub();
        $bitmaskNew = $bitmask->add('view');

        self::assertNotSame($bitmask, $bitmaskNew);

        $bitmask = new BitmaskStub();
        $bitmask = $bitmask
            ->add('view')
            ->add('eDiT', 'ownEr');

        $mask = $bitmask->get();

        self::assertTrue($bitmask->has(BitmaskStub::VIEW));
        self::assertTrue($bitmask->has('view'));

        self::assertEquals(BitmaskStub::VIEW, $mask & BitmaskStub::VIEW);
        self::assertEquals(BitmaskStub::EDIT, $mask & BitmaskStub::EDIT);
        self::assertEquals(BitmaskStub::OWNER, $mask & BitmaskStub::OWNER);
        self::assertTrue($bitmask->has(BitmaskStub::OWNER));

        self::assertEquals(0, $mask & BitmaskStub::MASTER);
        self::assertEquals(0, $mask & BitmaskStub::CREATE);
        self::assertEquals(0, $mask & BitmaskStub::DELETE);
        self::assertEquals(0, $mask & BitmaskStub::UNDELETE);

        // Remove
        $bitmaskRemoved = $bitmask->remove('edit', 'OWner');
        $mask = $bitmaskRemoved->get();

        self::assertNotSame($bitmask, $bitmaskRemoved);
        self::assertFalse($bitmaskRemoved->has(BitmaskStub::OWNER));
        self::assertEquals(0, $mask & BitmaskStub::EDIT);
        self::assertEquals(0, $mask & BitmaskStub::OWNER);
        self::assertEquals(BitmaskStub::VIEW, $mask & BitmaskStub::VIEW);
    }

    /** @test */
    public function it_clears(): void
    {
        $bitmask = new BitmaskStub();
        self::assertEquals(0, $bitmask->get());

        $bitmask = $bitmask->add('view');
        self::assertTrue($bitmask->get() > 0);

        $bitmask = $bitmask->clear();
        self::assertEquals(0, $bitmask->get());
    }
}

/** @internal */
final class BitmaskStub extends Bitmask
{
    public const VIEW = 1;        // 1 << 0
    public const CREATE = 2;      // 1 << 1
    public const EDIT = 4;        // 1 << 2
    public const DELETE = 8;      // 1 << 3
    public const UNDELETE = 16;   // 1 << 4
    public const OPERATOR = 32;   // 1 << 5
    public const MASTER = 64;     // 1 << 6
    public const OWNER = 128;     // 1 << 7
}
