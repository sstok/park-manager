<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Application\Command\Webhosting\ScheduledTask;

use ParkManager\Domain\Webhosting\ScheduledTask\ScheduledTaskRepository;
use ParkManager\Domain\Webhosting\ScheduledTask\Task;
use ParkManager\Domain\Webhosting\Space\SpaceRepository;

final class AddScheduledTaskHandler
{
    public function __construct(
        private ScheduledTaskRepository $repository,
        private SpaceRepository $spaceRepository,
    ) {}

    public function __invoke(AddScheduledTask $command): void
    {
        $space = $this->spaceRepository->get($command->space);
        $task = new Task($command->id, $space, $command->schedule, $command->command);

        $this->repository->save($task);
    }
}
