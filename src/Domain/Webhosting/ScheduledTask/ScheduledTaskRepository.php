<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\ScheduledTask;

use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\ScheduledTask\Exception\TaskNotFound;
use ParkManager\Domain\Webhosting\Space\SpaceId;

interface ScheduledTaskRepository
{
    /**
     * @throws TaskNotFound
     */
    public function get(TaskId $id): Task;

    /**
     * @return ResultSet<Task>
     */
    public function all(SpaceId $space): ResultSet;

    public function save(Task $task): void;

    public function remove(Task $task): void;
}
