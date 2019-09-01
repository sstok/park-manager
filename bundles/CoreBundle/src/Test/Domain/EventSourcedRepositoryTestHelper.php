<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Bundle\CoreBundle\Test\Domain;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

trait EventSourcedRepositoryTestHelper
{
    protected function createEventsExpectingEventBus(): MessageBusInterface
    {
        return new class implements MessageBusInterface
        {
            public function dispatch($message, array $stamps = []): Envelope
            {
                return new Envelope($message, $stamps);
            }
        };
    }
}
