<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command;

use Symfony\Component\Messenger\MessageBusInterface;

final class BatchCommandHandler
{
    public function __construct(private MessageBusInterface $commandBus) {}

    public function __invoke(BatchCommand $batch): void
    {
        foreach ($batch->commands as $command) {
            $this->commandBus->dispatch($command);
        }
    }
}
