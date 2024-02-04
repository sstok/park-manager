<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\ScheduledTask;

use ParkManager\Domain\Webhosting\ScheduledTask\ScheduledTaskRepository;

final class EnableScheduledTaskHandler
{
    public function __construct(private ScheduledTaskRepository $repository) {}

    public function __invoke(EnableScheduledTask $command): void
    {
        $task = $this->repository->get($command->id);
        $task->enable();

        $this->repository->save($task);
    }
}
