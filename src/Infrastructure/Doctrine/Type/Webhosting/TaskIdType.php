<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type\Webhosting;

use ParkManager\Domain\Webhosting\ScheduledTask\TaskId;
use ParkManager\Infrastructure\Doctrine\Type\DomainIdType;

final class TaskIdType extends DomainIdType
{
    public const NAME = 'park_manager_scheduled_task_id';
    public const OBJECT_CLASS = TaskId::class;
}
