<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Tests\Model;

use ParkManager\Bundle\CoreBundle\Model\Client\ClientId;
use ParkManager\Bundle\CoreBundle\Model\OwnerId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class OwnerIdTest extends TestCase
{
    private const USER_ID = '783d3266-955a-11e8-8b48-4a0003ae49a0';

    /** @test */
    public function it_creates_from_user_id(): void
    {
        $userId = ClientId::fromString(self::USER_ID);
        $id = OwnerId::fromUserId($userId);

        static::assertTrue($id->equals(OwnerId::fromUserId(ClientId::fromString(self::USER_ID))));
        static::assertFalse($id->equals(OwnerId::fromUserId(ClientId::fromString('fb676f62-955a-11e8-8ef5-4a0003ae49a0'))));
        static::assertFalse($id->equals($userId));

        static::assertTrue($id->is(OwnerId::PERSONAL));
        static::assertTrue($id->isOwnedBy($userId));
        static::assertTrue($id->isOwnedBy(ClientId::fromString(self::USER_ID)));
        static::assertFalse($id->is(OwnerId::INTERNAL));
        static::assertFalse($id->is(OwnerId::PRIVATE));
    }

    /**
     * @test
     */
    public function internal(): void
    {
        $id = OwnerId::internal();

        static::assertTrue($id->equals(OwnerId::internal()));
        static::assertFalse($id->equals(OwnerId::private()));
        static::assertFalse($id->equals(OwnerId::fromUserId(ClientId::fromString(self::USER_ID))));

        static::assertTrue($id->is(OwnerId::INTERNAL));
        static::assertFalse($id->is(OwnerId::PRIVATE));
        static::assertFalse($id->is(OwnerId::PERSONAL));
    }

    /**
     * @test
     */
    public function private(): void
    {
        $id = OwnerId::private();

        static::assertTrue($id->equals(OwnerId::private()));
        static::assertFalse($id->equals(OwnerId::internal()));
        static::assertFalse($id->equals(OwnerId::fromUserId(ClientId::fromString(self::USER_ID))));

        static::assertTrue($id->is(OwnerId::PRIVATE));
        static::assertFalse($id->is(OwnerId::INTERNAL));
        static::assertFalse($id->is(OwnerId::PERSONAL));
    }
}
