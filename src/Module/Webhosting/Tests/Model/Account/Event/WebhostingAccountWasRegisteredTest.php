<?php

declare(strict_types=1);

/*
 * Copyright (c) the Contributors as noted in the AUTHORS file.
 *
 * This file is part of the Park-Manager project.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Module\Webhosting\Tests\Model\Account\Event;

use ParkManager\Module\Webhosting\Model\Account\Event\WebhostingAccountWasRegistered;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountId;
use ParkManager\Module\Webhosting\Model\Account\WebhostingAccountOwner;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingAccountWasRegisteredTest extends TestCase
{
    private const ACCOUNT_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';
    private const OWNER_ID = '2a9cd25c-97ca-11e7-9683-acbc32b58315';

    /** @test */
    public function its_constructable()
    {
        $event = new WebhostingAccountWasRegistered(
            $id = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            $owner = WebhostingAccountOwner::fromString(self::OWNER_ID)
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($owner, $event->owner());
    }
}
