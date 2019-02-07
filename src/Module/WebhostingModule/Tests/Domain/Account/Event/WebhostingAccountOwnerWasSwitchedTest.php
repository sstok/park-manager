<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Account\Event;

use ParkManager\Module\CoreModule\Domain\Shared\OwnerId;
use ParkManager\Module\WebhostingModule\Domain\Account\Event\WebhostingAccountOwnerWasSwitched;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingAccountOwnerWasSwitchedTest extends TestCase
{
    /** @test */
    public function its_constructable(): void
    {
        $event = new WebhostingAccountOwnerWasSwitched(
            $id = WebhostingAccountId::fromString('b288e23c-97c5-11e7-b51a-acbc32b58315'),
            $oldOwner = OwnerId::fromString('2a9cd25c-97ca-11e7-9683-acbc32b58315'),
            $newOwner = OwnerId::fromString('ce18c388-9ba2-11e7-b15f-acbc32b58315')
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($oldOwner, $event->oldOwner());
        self::assertEquals($newOwner, $event->newOwner());
    }
}
