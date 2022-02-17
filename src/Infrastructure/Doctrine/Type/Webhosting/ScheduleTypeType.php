<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Infrastructure\Doctrine\Type\Webhosting;

use ParkManager\Domain\Webhosting\ScheduledTask\ScheduleType;
use ParkManager\Infrastructure\Doctrine\Type\EnumType;

final class ScheduleTypeType extends EnumType
{
    public const NAME = 'park_manager_task_schedule_type';
    public const OBJECT_CLASS = ScheduleType::class;
}
