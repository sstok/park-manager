<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Lifthill\Bridge\Doctrine\OrmQueryBuilderResultSet;
use Lifthill\Component\Common\Domain\ResultSet;
use ParkManager\Domain\Webhosting\ScheduledTask\Exception\TaskNotFound;
use ParkManager\Domain\Webhosting\ScheduledTask\ScheduledTaskRepository;
use ParkManager\Domain\Webhosting\ScheduledTask\Task;
use ParkManager\Domain\Webhosting\ScheduledTask\TaskId;
use ParkManager\Domain\Webhosting\Space\SpaceId;

/**
 * @extends EntityRepository<Task>
 */
final class ScheduledTaskRepositoryOrmRepository extends EntityRepository implements ScheduledTaskRepository
{
    public function __construct(EntityManagerInterface $entityManager, string $className = Task::class)
    {
        parent::__construct($entityManager, $className);
    }

    public function get(TaskId $id): Task
    {
        $plan = $this->find($id->toString());

        if ($plan === null) {
            throw TaskNotFound::withId($id);
        }

        return $plan;
    }

    public function all(SpaceId $space): ResultSet
    {
        return new OrmQueryBuilderResultSet($this->createQueryBuilder('t'), 't', false);
    }

    public function save(Task $task): void
    {
        $this->_em->persist($task);
    }

    public function remove(Task $task): void
    {
        $this->_em->remove($task);
    }
}
