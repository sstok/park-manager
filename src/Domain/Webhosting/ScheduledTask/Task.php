<?php

declare(strict_types=1);

/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */

namespace ParkManager\Domain\Webhosting\ScheduledTask;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use Lifthill\Component\Common\Domain\Attribute\Entity as DomainEntity;
use ParkManager\Domain\Webhosting\Space\Space;

#[Entity]
#[Table(name: 'scheduled_task')]
#[DomainEntity]
class Task
{
    public function __construct(
        #[Id]
        #[Column(type: 'park_manager_scheduled_task_id')]
        #[GeneratedValue(strategy: 'NONE')]
        public TaskId $id,

        #[ManyToOne(targetEntity: Space::class)]
        #[JoinColumn(name: 'space', referencedColumnName: 'id', nullable: false, onDelete: 'RESTRICT')]
        public Space $space,

        #[Embedded(class: Schedule::class, columnPrefix: 'schedule_')]
        public Schedule $schedule,

        #[Column(type: 'text')]
        public string $command,

        #[Column(name: 'is_enabled', type: 'boolean')]
        public bool $enabled = true,
    ) {
    }

    public function setSchedule(Schedule $schedule): void
    {
        if (! $this->schedule->equals($schedule)) {
            $this->schedule = $schedule;
        }
    }

    public function changeCommand(string $command): void
    {
        $this->command = $command;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }
}
