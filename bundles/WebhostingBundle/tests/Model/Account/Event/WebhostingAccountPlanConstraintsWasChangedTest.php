<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\WebhostingBundle\Tests\Model\Account\Event;

use ParkManager\Bundle\WebhostingBundle\Model\Account\Event\WebhostingAccountPlanConstraintsWasChanged;
use ParkManager\Bundle\WebhostingBundle\Model\Account\WebhostingAccountId;
use ParkManager\Bundle\WebhostingBundle\Model\Plan\Constraints;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class WebhostingAccountPlanConstraintsWasChangedTest extends TestCase
{
    private const ACCOUNT_ID = 'b288e23c-97c5-11e7-b51a-acbc32b58315';

    /** @test */
    public function its_constructable(): void
    {
        $constraints = new Constraints();

        $event = new WebhostingAccountPlanConstraintsWasChanged(
            $id = WebhostingAccountId::fromString(self::ACCOUNT_ID),
            $constraints
        );

        self::assertTrue($id->equals($event->id()));
        self::assertEquals($constraints, $event->constraints());
    }
}
