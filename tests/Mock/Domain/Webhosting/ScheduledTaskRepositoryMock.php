<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Tests\Mock\Domain\Webhosting;

use ParkManager\Domain\ResultSet;
use ParkManager\Domain\Webhosting\ScheduledTask\Exception\TaskNotFound;
use ParkManager\Domain\Webhosting\ScheduledTask\ScheduledTaskRepository;
use ParkManager\Domain\Webhosting\ScheduledTask\Task;
use ParkManager\Domain\Webhosting\ScheduledTask\TaskId;
use ParkManager\Domain\Webhosting\Space\SpaceId;
use ParkManager\Tests\Mock\Domain\MockRepository;

/**
 * @internal
 */
final class ScheduledTaskRepositoryMock implements ScheduledTaskRepository
{
    /** @use MockRepository<Task> */
    use MockRepository;

    protected function getFieldsIndexMultiMapping(): array
    {
        return [
            'space' => static fn (Task $task): string => (string) $task->space->id,
        ];
    }

    public function get(TaskId $id): Task
    {
        return $this->mockDoGetById($id);
    }

    public function all(SpaceId $space): ResultSet
    {
        return $this->mockDoGetMultiByField('space', $space->toString());
    }

    public function save(Task $task): void
    {
        $this->mockDoSave($task);
    }

    public function remove(Task $task): void
    {
        $this->mockDoRemove($task);
    }

    protected function throwOnNotFound(mixed $key): void
    {
        throw TaskNotFound::withId($key);
    }
}
