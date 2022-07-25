<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\ScheduledTask;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;

#[Embeddable]
final class Schedule
{
    #[Column(name: 'type', enumType: ScheduleType::class)]
    public ScheduleType $type;

    public function __construct(
        #[Column(name: 'condition', type: 'park_manager_scheduled_task_condition')]
        public ScheduleCondition $condition,
    ) {
        $this->type = $condition instanceof CronCondition ? ScheduleType::CRON : ScheduleType::MOMENT;
    }

    public function equals(self $other): bool
    {
        return (string) $this->condition === (string) $other->condition;
    }
}
