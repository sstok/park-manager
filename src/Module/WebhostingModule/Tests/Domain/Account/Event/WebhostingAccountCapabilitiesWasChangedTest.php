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

namespace ParkManager\Module\WebhostingModule\Tests\Domain\Account\Event;

use ParkManager\Module\WebhostingModule\Domain\Account\Event\WebhostingAccountCapabilitiesWasChanged;
use ParkManager\Module\WebhostingModule\Domain\Account\WebhostingAccountId;
use ParkManager\Module\WebhostingModule\Domain\Package\Capabilities;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingAccountCapabilitiesWasChangedTest extends TestCase
{
    private const ACCOUNT_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';

    /** @test */
    public function its_constructable(): void
    {
        $capabilities = new Capabilities();

        $event = new WebhostingAccountCapabilitiesWasChanged(
            $id = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            $capabilities
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($capabilities, $event->capabilities());
    }
}
