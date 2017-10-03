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

namespace ParkManager\Component\Model\Test;

use Prooph\ServiceBus\EventBus;
use Prophecy\Argument;

trait EventSourcedRepositoryTestHelper
{
    protected function createEventsExpectingEventBus(int $expectedEventsCount = -1): EventBus
    {
        $eventBusProphecy = $this->prophesize(EventBus::class);

        if (-1 === $expectedEventsCount) {
            $eventBusProphecy->dispatch(Argument::any())->shouldBeCalled();
        } else {
            $eventBusProphecy->dispatch(Argument::any())->shouldBeCalledTimes($expectedEventsCount);
        }

        return $eventBusProphecy->reveal();
    }

    protected function createNoExpectedEventsDispatchedEventBus(): EventBus
    {
        $eventBusProphecy = $this->prophesize(EventBus::class);
        $eventBusProphecy->dispatch(Argument::any())->shouldNotBeCalled();

        return $eventBusProphecy->reveal();
    }
}
